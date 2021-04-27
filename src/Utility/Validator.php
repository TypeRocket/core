<?php
namespace TypeRocket\Utility;

use TypeRocket\Exceptions\RedirectError;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
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
    protected $errorMessages = ['messages' => [], 'regex' => false];
    protected $ran = false;
    protected $validatorMap = [
        CallbackValidator::KEY => CallbackValidator::class,
        EmailValidator::KEY => EmailValidator::class,
        KeyValidator::KEY => KeyValidator::class,
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
     * @param array $rules the rules and validation handler
     * @param array|\ArrayObject|null $fields the fields to be validated
     * @param null $modelClass must be a class of Model
     * @param bool $run run validation on new
     */
    public function __construct($rules, $fields = null, $modelClass = null, $run = false)
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
            if($flash) {
                $response = \TypeRocket\Http\Response::getFromContainer();
                $this->flashErrors($response);
                $response->lockFlash();
            }

            $redirect = \TypeRocket\Http\Redirect::new()->withOldFields()->withErrors([$key => $this->getErrorFields()])->back();

            if(is_callable($callback)) {
                call_user_func($callback, $redirect);
            }

            throw (new RedirectError(__('Validation failed.', 'typerocket-domain')))->redirect( $redirect );
        }

        return $this;
    }

    /**
     * @param null $callback
     * @param bool $flash flash errors to page
     * @param string $key
     *
     * @return $this
     */
    public function respondWithErrors($callback = null, $flash = true, $key = 'fields')
    {
        if( $this->failed() && $this->ran) {

            $response = \TypeRocket\Http\Response::getFromContainer()
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
    public function getErrors() {
        return $this->errors;
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
    public function passed() {
        return empty($this->errors) && $this->ran;
    }

    /**
     * Check of failed
     *
     * @return bool
     */
    public function failed() {
        return !$this->passed();
    }

    /**
     * Flash validator errors on next request
     *
     * @param \TypeRocket\Http\Response $response
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
    private function mapFieldsToValidation() {
        foreach ($this->rules as $path => $handle) {
            $this->walk($this->fields, $path, $handle, $path);
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
     * @param array $arr
     * @param string $path
     * @param string $handle
     * @param string $fullPath
     *
     * @return array|null
     * @throws \Exception
     */
    protected function walk(array &$arr, $path, $handle, $fullPath) {
        $loc = &$arr;
        $dots = explode('.', $path);
        foreach($dots as $step)
        {
            array_shift($dots);
            if(in_array($step, ['*', '?']) && is_array($loc)) {
                $new_loc = &$loc;
                $indies = array_keys($new_loc);
                foreach($indies as $index) {
                    if(isset($new_loc[$index])) {
                        $newFullPath = preg_replace('(\*|\?)', "{$index}", $fullPath, 1);
                        $this->walk($new_loc[$index], implode('.', $dots), $handle, $newFullPath);
                    }
                }
            } elseif( $step === '?' && empty($loc) ) {
                $this->passes[substr($fullPath, 0, strpos($fullPath, '.?'))] = $loc;
                return null;
            } elseif( isset($loc[$step]) ) {
                $loc = &$loc[$step];
            } else {
                if( !empty($handle) && !isset($indies) ) {
                    $this->validateField( $handle, null, $fullPath );
                }

                return null;
            }

        }

        if(!isset($indies) && !empty($handle)) {
            $this->validateField( $handle, $loc, $fullPath );
        }

        return $loc;
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
     * @param string $name
     * @param string $field_name
     * @param ValidatorRule $class
     */
    protected function setErrorMessage($name, $field_name, $class) {
        $message = __($class->getError(), 'typerocket-domain');
        $this->errors[$name] = $field_name . ' ' .  $message;
        $this->errorFields[$name] = trim($message);
        $type = $class::KEY;
        $index = $name.':'.$type;
        $validate = $value = $match = $matches = false;

        if($this->errorMessages['regex'] && !empty($this->errorMessages['messages'])) {
            foreach ($this->errorMessages['messages'] as $key => $value) {
                $match = preg_match_all("/{$key}/", $index, $matches, PREG_SET_ORDER, 0);
                if($match) {
                    $validate = true;
                    break;
                }
            }
        }
        else {
            $validate = !empty($this->errorMessages['messages'][$index]);

            if($validate) {
                $value = $this->errorMessages['messages'][$index];
            }
        }

        if($validate) {
            if(is_callable($value)) {
                $this->errors[$name] = call_user_func($value, $name, $type, $this->errors[$name], $matches);
                $this->errorFields[$name] = $this->errors[$name];
            } else {
                $error_message = $class->getError();
                $error_message = isset($value) ? str_replace('{error}', $error_message, $value) : $error_message;
                $this->errors[$name] = $error_message;
                $this->errorFields[$name] = $error_message;
            }
        }
    }

    /**
     * Validate the Field
     *
     * @param string|ValidatorRule $handle
     * @param string $value
     * @param string $fullName
     *
     * @throws \Exception
     */
    protected function validateField($handle, $value, $fullName) {
        $field_name = '<strong>"' . Str::uppercaseWords(preg_replace('/\_|\./', ' ', $fullName)) . '"</strong>';

        $args = [
            'validator' => $this,
            'value' => $value,
            'full_name' => $fullName,
            'field_name' => $field_name,
        ];

        if($handle instanceof ValidatorRule) {
            $handle->setArgs($args);
            $this->runValidatorRule($handle, $fullName, $field_name, $value);
            return;
        }

        $list = [];

        if(is_string($handle)) {
            $handle = explode('|', (string) $handle);
        }

        if(is_array($handle)) {
            $list = $handle;
        }

        foreach( $list as $validation) {

            if(is_string($validation)) {
                [ $type, $option, $option2, $option3 ] = array_pad(explode(':', $validation, 4), 4, null);

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
                    ]);

                    $class = new $class;
                }
            }
            elseif ($validation instanceof ValidatorRule) {
                $class = $validation;
            }

            if($class instanceof ValidatorRule) {
                $class->setArgs($args);
                $this->runValidatorRule($class, $fullName, $field_name, $value);
                continue;
            }

            throw new \Exception('Unknown validation option: ' . $type);
        }
    }

    /**
     * @param ValidatorRule $class
     * @param $fullName
     * @param $field_name
     * @param $value
     */
    protected function runValidatorRule(ValidatorRule $class, $fullName, $field_name, $value) {
        $pass = $class->validate();

        if( !$pass ) {
            $this->setErrorMessage($fullName, $field_name, $class);
        } else {
            $this->passes[$fullName . ':' . $class::KEY] = $value;
        }
    }
}