<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

class CallbackValidator extends ValidatorRule
{
    public CONST KEY = 'callback';

    public function validate(): bool
    {
        /**
         * @var $option
         * @var $option2
         * @var $option3
         * @var $full_name
         * @var $field_name
         * @var $value
         * @var $type
         * @var Validator $validator
         */
        extract($this->args);

        $error = $option($this->args);

        if( is_string($error) ) {
            $this->error = $error;
        }

        return !$this->error;
    }
}