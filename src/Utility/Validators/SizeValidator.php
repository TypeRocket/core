<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

class SizeValidator extends ValidatorRule
{
    public CONST KEY = 'size';

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

        $option = (int) $option;

        if( mb_strlen($value) !== (int) $option ) {
            $this->error = "must be $option exactly characters.";
        }

        return !$this->error;
    }
}