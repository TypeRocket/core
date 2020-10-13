<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

class NumericValidator extends ValidatorRule
{
    public CONST KEY = 'numeric';

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

        if( ! is_numeric($value) ) {
            $this->error = "must be a numeric value.";
        }

        return !$this->error;
    }
}