<?php
namespace TypeRocket\Utility;

use TypeRocket\Exceptions\RedirectError;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Http\Redirect;
use TypeRocket\Utility\Validators\DateTimeLocalValidator;
use TypeRocket\Utility\Validators\ValidatorRule;
use TypeRocket\Utility\Validators\CallbackValidator;
use TypeRocket\Utility\Validators\EmailValidator;
use TypeRocket\Utility\Validators\KeyValidator;
use TypeRocket\Utility\Validators\MaxLengthValidator;
use TypeRocket\Utility\Validators\MinLengthValidator;
use TypeRocket\Utility\Validators\NumericValidator;
use TypeRocket\Utility\Validators\RequiredValidator;
use TypeRocket\Utility\Validators\SizeValidator;
use TypeRocket\Utility\Validators\UniqueModelValidator;
use TypeRocket\Utility\Validators\UrlValidator;

class Validator
{
    protected $rules = [];
    protected $fields = [];
    protected $passes = [];
    protected $errors = [];
    protected $errorFields = [];
    protected $errorFieldsGroup;
    protected $modelClass;
    protected $callbacks = [
        'fieldLabel' => null
    ];
    protected $errorMessages = ['messages' => [], 'regex' => false];
    protected $ran = false;
    protected $validatorMap = [
        CallbackValidator::KEY => CallbackValidator::class,
        EmailValidator::KEY => EmailValidator::class,
        KeyValidator::KEY => KeyValidator::class,
        DateTimeLocalValidator::KEY => DateTimeLocalValidator::class,
        MaxLengthValidator::KEY => MaxLengthValidator::class,
        MinLengthValidator::KEY => MinLengthValidator::class,
        NumericValidator::KEY => NumericValidator::class,
        RequiredValidator::KEY => RequiredValidator::class,
        SizeValidator::KEY => SizeValidator::class,
        UniqueModelValidator::KEY => UniqueModelValidator::class,
        UrlValidator::KEY => UrlValidator::class,
    ];

    /**
     * Validator
     *
     * Validate data mapped to fields
     *
     * @param array $rules the rules and validation handlers
     * @param array|\ArrayObject|null $fields the fields to be validated
     * @param null|string $modelClass must be a class of Model
     * @param bool $run run validation on new
     */
    public function __construct(array $rules, $fields = null, $modelClass = null, $run = false)
    {
        $this->modelClass = $modelClass;
        $this->fields = $fields ?? (new Request)->getFields();
        $this->rules = $rules;
        $this->validatorMap = apply_filters('typerocket_validator_map', $this->validatorMap);

        if($run) {
            $this->mapFieldsToValidation();
        }
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }

    /**
     * Run Validation
     *
     * @param bool $returnSelf
     *
     * @return bool|$this
     */
    public function validate($returnSelf = false)
    {
        $this->mapFieldsToValidation();

        return $returnSelf ? $this : $this->passed();
    }

    /**
     * @param string $key
     *
     * @return Redirect
     */
    public function getRedirectWithFieldErrors($key = 'fields')
    {
        return Redirect::new()->withFieldErrors($this->getErrorFields(), $key);
    }

    /**
     * @param null|callable $callback
     * @param bool $flash flash errors to page
     * @param string $key
     *
     * @return $this
     * @throws RedirectError
     */
    public function redirectWithErrorsIfFailed($callback = null, $flash = true, $key = 'fields')
    {
        if($this->failed()) {
            $redirect = $this->getRedirectIfFailedWithFieldsAndErrors($flash, $key)->back();

            if(is_callable($callback)) {
                call_user_func($callback, $redirect);
            }

            throw (new RedirectError(__('Validation failed.', 'typerocket-domain')))->redirect( $redirect );
        }

        return $this;
    }

    /**
     * @param bool $flash flash errors to page
     * @param string $key
     *
     * @return Redirect
     */
    public function getRedirectIfFailedWithFieldsAndErrors($flash = true, $key = 'fields')
    {
        $redirect = null;

        if($this->failed()) {
            if($flash) {
                $response = Response::getFromContainer();
                $this->flashErrors($response);
                $response->lockFlash();
            }

            $redirect = $this->getRedirectWithFieldErrors($key)->withOldFields();
        }

        return $redirect ?? Redirect::new();
    }

    /**
     * @param null|callable $callback
     * @param bool $flash flash errors to page
     * @param string $key
     *
     * @return $this
     */
    public function respondWithErrors($callback = null, $flash = true, $key = 'fields')
    {
        if( $this->failed() && $this->ran) {

            $response = Response::getFromContainer()
                ->withOldFields()
                ->setError($key, $this->getErrorFields())
                ->withRedirectErrors();

            if($flash) {
                $this->flashErrors($response->allowFlash());
                $response->lockFlash();
            }

            if(is_callable($callback)) {
                call_user_func($callback, $response);
            }
        }

        return $this;
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Has Errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getError(string $key)
    {
        return $this->errors[$key] ?? null;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getErrorField(string $key)
    {
        return $this->errorFields[$key] ?? null;
    }

    /**
     * Get passes
     *
     * @return array
     */
    public function getPasses()
    {
        return $this->passes;
    }

    /**
     * @return array
     */
    public function getErrorFields()
    {
        return $this->errorFields;
    }

    /**
     * Set Error Messages
     *
     * @param array $messages
     * @param bool $regex search for key using regex
     * @return $this
     */
    public function setErrorMessages(array $messages, $regex = false)
    {
        $this->errorMessages = [ 'messages' => $messages, 'regex' => $regex ];
        return $this;
    }

    /**
     * Check if passes
     *
     * @return bool
     */
    public function passed()
    {
        return empty($this->errors) && $this->ran;
    }

    /**
     * Check of failed
     *
     * @return bool
     */
    public function failed()
    {
        return !$this->passed();
    }

    /**
     * Flash validator errors on next request
     *
     * @param Response $response
     *
     * @return $this
     */
    public function flashErrors( Response $response )
    {
        $errors = '<ul>';

        $list = array_unique($this->errors);

        foreach ($list as $error ) {
            $errors .= "<li>$error</li>";
        }

        $errors .= '</ul>';

        $response->flashNext($errors, 'error');

        return $this;
    }

    /**
     * Map fields to validators
     */
    protected function mapFieldsToValidation()
    {
        foreach ($this->rules as $fullDotPath => $validationRules) {
            $this->walk($this->fields, $fullDotPath, $validationRules, $fullDotPath);
        }

        $this->ran = true;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }

    /**
     * Used to format fields
     *
     * @param array $fields array of fields to validate
     * @param string $dotPath seeking fields path in dot notation
     * @param array|string|ValidatorRule $validationRules
     * @param string $fullDotPath main fields path in dot notation
     *
     * @return array|null
     * @throws \Exception
     */
    protected function walk(array &$fields, string $dotPath, $validationRules, string $fullDotPath)
    {
        $value = &$fields;
        $dots = explode('.', $dotPath);
        foreach($dots as $step)
        {
            array_shift($dots);
            if(in_array($step, ['*', '?']) && is_array($value)) {
                $new_loc = &$value;
                $indies = array_keys($new_loc);
                foreach($indies as $index) {
                    if(isset($new_loc[$index])) {
                        $newFullPath = preg_replace('(\*|\?)', "{$index}", $fullDotPath, 1);
                        $this->walk($new_loc[$index], implode('.', $dots), $validationRules, $newFullPath);
                    }
                }
            } elseif( $step === '?' && empty($value) ) {
                $this->passes[substr($fullDotPath, 0, strpos($fullDotPath, '.?'))] = $value;
                return null;
            } elseif( isset($value[$step]) ) {
                $value = &$value[$step];
            } else {
                if( !empty($validationRules) && !isset($indies) ) {
                    $this->validateField( $validationRules, null, $fullDotPath );
                }
                return null;
            }
        }

        if(!isset($indies) && !empty($validationRules)) {
            $this->validateField( $validationRules, $value, $fullDotPath );
        }

        return $value;
    }

    /**
     * @param $message
     *
     * @return $this
     */
    public function appendToFlashErrorMessage($message)
    {
        $this->errors[] = $message;

        return $this;
    }

    /**
     * @param $message
     *
     * @return $this
     */
    public function prependToFlashErrorMessage($message)
    {
        array_unshift($this->errors, $message);

        return $this;
    }

    /**
     * Set Error Message
     *
     * Pulls message override from $errorMessages
     *
     * @param ValidatorRule $class
     * @param string $fullDotPath name in dot notation
     */
    protected function setErrorMessage(ValidatorRule $class, $fullDotPath)
    {
        $error_message = $class->getError();
        $error_message_full = $class->getFieldLabel() . ' ' .  $error_message;
        $type = $class::KEY;
        $index = $fullDotPath.':'.$type;
        $validate = $value = $matches = false;

        if($this->errorMessages['regex'] && !empty($this->errorMessages['messages'])) {
            foreach ($this->errorMessages['messages'] as $key => $value) {
                if(preg_match_all("/{$key}/", $index, $matches, PREG_SET_ORDER, 0)) {
                    $validate = true;
                    break;
                }
            }
        } else {
            $validate = !empty($this->errorMessages['messages'][$index]);

            if($validate) {
                $value = $this->errorMessages['messages'][$index];
            }
        }

        if($validate) {
            if(is_callable($value)) {
                $error_message = $error_message_full = call_user_func($value, $fullDotPath, $type, $error_message_full, $matches, $error_message);
            } else {
                $error_message = $error_message_full = isset($value) ? str_replace('{error}', $error_message, $value) : $error_message;
            }

            if(is_array($error_message)) {
                $error_message_full = $error_message['full'];
                $error_message = $error_message['field'];
            }
        }

        $this->errors[$fullDotPath] = $this->errors[$fullDotPath] ?? $error_message_full;
        $this->errorFields[$fullDotPath] = $this->errorFields[$fullDotPath] ?? trim($error_message);
    }

    /**
     * @param string $key
     * @param callable $callback
     *
     * @return $this
     */
    public function setCallback(string $key, callable $callback)
    {
        $this->callbacks[$key] = $callback;

        return $this;
    }

    /**
     * Validate the Field
     *
     * @param string|array|ValidatorRule $validationRules
     * @param array|string $value
     * @param string $fullDotPath
     *
     * @throws \Exception
     */
    protected function validateField($validationRules, $value, string $fullDotPath)
    {
        if($this->callbacks['fieldLabel']) {
            $fieldLabel = call_user_func($this->callbacks['fieldLabel'], $fullDotPath, $this, $value);
        } else {
            $fieldLabel = '<strong>"' . Str::uppercaseWords(preg_replace('/\_|\./', ' ', $fullDotPath)) . '"</strong>';
        }

        $args = [
            'validator' => $this,
            'value' => $value,
            'full_name' => $fullDotPath,
            'field_name' => $fieldLabel,
            'field_label' => $fieldLabel,
        ];

        if($validationRules instanceof ValidatorRule) {
            $validationRules->setArgs($args);
            $this->runValidatorRule($validationRules, $fullDotPath, $value);
            return;
        }

        $list = [];
        $weak_all = null;

        if(is_string($validationRules)) {
            if($validationRules[0] === '?') {
                $weak_all = true;
                $validationRules = substr($validationRules, 1);
            }

            $validationRules = explode('|', (string) $validationRules);
        }

        if(is_array($validationRules)) {
            $list = $validationRules;
        }

        foreach($list as $validation)
        {
            $class = $weak = $subfields = null;
            $value_checked = $value;

            if(is_string($validation)) {
                [ $type, $option, $option2, $option3 ] = array_pad(explode(':', $validation, 4), 4, null);

                if($type[0] === '?') {
                    $weak = true;
                    $type = substr($type, 1);
                }

                $weak = $weak ?? $weak_all;

                if(Str::starts('only_subfields=', $option)) {
                    $only_subfields = explode('/', $option)[0];
                    $subfields = explode(',', substr($only_subfields, 15));
                    $value_checked = Arr::only($value, $subfields);

                    $value_checked = array_filter($value_checked, function($v) {
                        return isset($v);
                    });

                    $value_checked = Arr::isEmptyArray($value_checked) ? null : $value_checked;

                    $option = substr($option, strlen($only_subfields) + 1) ?: null;
                }

                if(array_key_exists($type, $this->validatorMap)) {
                    $class = $this->validatorMap[$type];
                } else {
                    $class = $type;
                }

                if(class_exists($class)) {
                    $args = array_merge($args, [
                        'option' => $option,
                        'option2' => $option2,
                        'option3' => $option3,
                        'weak' => $weak,
                        'value' => $value_checked,
                        'subfields' => $subfields,
                    ]);

                    $class = new $class;
                }
            }
            elseif ($validation instanceof ValidatorRule) {
                $class = $validation;
            }

            if($class instanceof ValidatorRule) {
                $class->setArgs($args);
                $this->runValidatorRule($class, $fullDotPath, $value_checked);
                continue;
            }

            throw new \Exception('Unknown validation option: ' . $type);
        }
    }

    /**
     * @param ValidatorRule $rule
     * @param string $fullDotPath
     * @param mixed $value
     */
    protected function runValidatorRule(ValidatorRule $rule, string $fullDotPath, $value)
    {
        if($rule->isOptional() && Data::emptyOrBlankRecursive($value)) {
            $pass = true;
        } else {
            $pass = $rule->validate();
        }

        if( !$pass ) {
            $this->setErrorMessage($rule, $fullDotPath);
        } else {
            $this->passes[$fullDotPath . ':' . $rule::KEY] = $value;
        }
    }
}