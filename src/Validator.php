<?php

namespace TypeRocket;

use TypeRocket\Http\Response;

class Validator
{
    public $errors = [];
    public $options = [];
    public $fields = [];
    public $modelClass = [];

    /**
     * Validator
     *
     * Validate data mapped to fields
     *
     * @param array $options the options and validation handler
     * @param array $fields the fields to be validated
     * @param null $modelClass must be a class of SchemaModel
     */
    public function __construct($options, $fields, $modelClass = null)
    {
        $this->modelClass = $modelClass;
        $this->fields = $fields;
        $this->options = $options;

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
     *
     * @return array
     */
    private function mapFieldsToValidation() {
        foreach ($this->options as $path => $handle) {
            $this->ArrayDots($this->fields, $path, $handle);
        }
    }

    /**
     * Used to format fields
     *
     * @param array $arr
     * @param $path
     * @param $handle
     *
     * @return array|null
     */
    private function ArrayDots(array &$arr, $path, $handle) {
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
                        $this->ArrayDots($new_loc[$index], implode('.', $dots), $handle);
                    }
                }
            } elseif( isset($loc[$step] ) ) {
                $loc = &$loc[$step];
            } else {
                return null;
            }

        }

        if(!isset($indies)) {
            if( !empty($handle) ) {
                $this->validateField( $handle, $loc, $path );
            }
        }

        return $loc;
    }

    /**
     * Validate the Field
     *
     * @param $handle
     * @param $value
     * @param $name
     */
    private function validateField( $handle, $value, $name ) {
        $list = explode('|', $handle);
        foreach( $list as $validation) {
            list( $type, $option, $option2 ) = explode(':', $validation, 3);
            $field_name = '"<strong>' . ucwords(preg_replace('/\_/', ' ', $name)) . '</strong>"';
            switch($type) {
                case 'required' :
                    if( empty( $value ) ) {
                        $this->errors[$name] =  $field_name . ' is required.';
                    }
                    break;
                case 'length' :
                    if( mb_strlen($value) < $option ) {
                        $this->errors[$name] =  $field_name . " must be at least $option characters long.";
                    }
                    break;
                case 'email' :
                    if( ! filter_var($value, FILTER_VALIDATE_EMAIL) ) {
                        $this->errors[$name] =  $field_name . " must be at an email address.";
                    }
                    break;
                case 'numeric' :
                    if( ! is_numeric($value) ) {
                        $this->errors[$name] =  $field_name . " must be a numeric value.";
                    }
                    break;
                case 'url' :
                    if( ! filter_var($value, FILTER_VALIDATE_URL) ) {
                        $this->errors[$name] =  $field_name . " must be at a URL.";
                    }
                    break;
                case 'unique' :
                    if( $this->modelClass ) {
                        /** @var \TypeRocket\Models\SchemaModel $model */
                        $model = new $this->modelClass;
                        $model->where($option, $value);

                        if($option2) {
                            $model->where($model->idColumn, '!=', $option2);
                        }

                        $result = $model->first();
                        if($result) {
                            $this->errors[$name] =  $field_name . ' is taken.';
                        }
                    }
                    break;
            }
        }
    }
}