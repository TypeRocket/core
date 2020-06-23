<?php

namespace TypeRocket\Utility;

use TypeRocket\Database\Query;
use TypeRocket\Http\Response;

class Validator
{
    public $errors = [];
    public $errorMessages = ['messages' => [], 'regex' => false];
    public $passes = [];
    public $rules = [];
    public $fields = [];
    public $modelClass = [];

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
    public function __construct($rules, $fields, $modelClass = null, $run = true)
    {
        $this->modelClass = $modelClass;
        $this->fields = $fields ?? [];
        $this->rules = $rules;

        if($run) {
            $this->mapFieldsToValidation();
        }
    }

    /**
     * Run Validation
     */
    public function validate()
    {
        $this->mapFieldsToValidation();
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
        return empty($this->errors);
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
     */
    public function flashErrors( Response $response )
    {
        $errors = '<ul>';

        foreach ($this->errors as $error ) {
            $errors .= "<li>$error</li>";
        }

        $errors .= '</ul>';

        $response->flashNext($errors, 'error');
    }

    /**
     * Map fields to validators
     */
    private function mapFieldsToValidation() {
        foreach ($this->rules as $path => $handle) {
            $this->walk($this->fields, $path, $handle, $path);
        }
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
    private function walk(array &$arr, $path, $handle, $fullPath) {
        $loc = &$arr;
        $dots = explode('.', $path);
        foreach($dots as $step)
        {
            array_shift($dots);
            if($step === '*' && is_array($loc)) {
                $new_loc = &$loc;
                $indies = array_keys($new_loc);
                foreach($indies as $index) {
                    if(isset($new_loc[$index])) {
                        $newFullPath = preg_replace('(\*)', "{$index}", $fullPath, 1);
                        $this->walk($new_loc[$index], implode('.', $dots), $handle, $newFullPath);
                    }
                }
            } elseif( isset($loc[$step] ) ) {
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
     * Set Error Message
     *
     * Pulls message override from $errorMessages
     *
     * @param string $name
     * @param string $type
     * @param string $message
     */
    protected function setErrorMessage($name, $type, $message) {
        $this->errors[$name] = $message;
        $index = $name.':'.$type;
        $validate = $value = $match = $matches = false;

        if($this->errorMessages['regex']) {
            foreach ($this->errorMessages['messages'] as $key => $value) {
                $match = preg_match_all("/{$key}/", $index, $matches, PREG_SET_ORDER, 0);
                if($match) {
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
                $this->errors[$name] = call_user_func($value, $name, $type, $message, $matches);
            } else {
                $this->errors[$name] = $value;
            }

        }
    }

    /**
     * Validate the Field
     *
     * @param string $handle
     * @param string $value
     * @param string $name
     * @throws \Exception
     */
    protected function validateField( $handle, $value, $name ) {
        $list = explode('|', $handle);
        foreach( $list as $validation) {
            list( $type, $option, $option2, $option3 ) = array_pad(explode(':', $validation, 4), 4, null);
            $field_name = '"<strong>' . ucwords(preg_replace('/\_|\./', ' ', $name)) . '</strong>"';
            switch($type) {
                case 'required' :
                    if( empty( $value ) ) {
                        $this->setErrorMessage($name, $type, $field_name . ' is required.');
                    } else {
                        $this->passes[$name] = $value;
                    }
                    break;
                case 'callback' :
                    $callback_value = call_user_func_array($option, [ $this, $value, $field_name, $option2 ]);
                    if( isset($callback_value['error']) ) {
                        $this->setErrorMessage($name, $type, $callback_value['error']);
                    } else {
                        $this->passes[$name] = $callback_value['success'];
                    }
                    break;
                case 'min' :
                    $option = (int) $option;
                    if( mb_strlen($value) < $option ) {
                        $this->setErrorMessage($name, $type, $field_name . " must be at least $option characters.");
                    } else {
                        $this->passes[$name] = $value;
                    }
                    break;
                case 'max' :
                    $option = (int) $option;
                    if( mb_strlen($value) > $option ) {
                        $this->setErrorMessage($name, $type, $field_name . " must be less than $option characters.");
                    } else {
                        $this->passes[$name] = $value;
                    }
                    break;
                case 'size' :
                    $option = (int) $option;
                    if( mb_strlen($value) !== (int) $option ) {
                        $this->setErrorMessage($name, $type, $field_name . " must be $option characters.");
                    } else {
                        $this->passes[$name] = $value;
                    }
                    break;
                case 'email' :
                    if( ! filter_var($value, FILTER_VALIDATE_EMAIL) ) {
                        $this->setErrorMessage($name, $type, $field_name . " must be an email address.");
                    } else {
                        $this->passes[$name] = $value;
                    }
                    break;
                case 'numeric' :
                    if( ! is_numeric($value) ) {
                        $this->setErrorMessage($name, $type, $field_name . " must be a numeric value.");
                    } else {
                        $this->passes[$name] = $value;
                    }
                    break;
                case 'url' :
                    if( ! filter_var($value, FILTER_VALIDATE_URL) ) {
                        $this->setErrorMessage($name, $type, $field_name . " must be at a URL.");
                    } else {
                        $this->passes[$name] = $value;
                    }
                    break;
                case 'unique' :
                    $result = null;

                    if( $this->modelClass && ! $option3) {
                        /** @var \TypeRocket\Models\Model $model */
                        $model = new $this->modelClass;
                        $model->where($option, $value);

                        if($option2) {
                            $model->where($model->idColumn, '!=', $option2);
                        }

                        $result = $model->first();
                    } elseif( $option3 || ( ! $this->modelClass && $option2 ) ) {
                        list($table, $idColumn) = array_pad(explode('@', $option2, 2), 2, null);
                        $query = (new Query())->table($table)->where($option, $value);

                        if($idColumn && $option3) {
                            $query->where($idColumn, '!=', $option3);
                        }

                        $result = $query->first();
                    }

                    if($result) {
                        $this->setErrorMessage($name, $type, $field_name . ' is taken.');
                    } else {
                        $this->passes[$name] = $value;
                    }

                    break;
            }
        }
    }
}
