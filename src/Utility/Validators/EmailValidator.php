<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

class EmailValidator extends ValidatorRule
{
    public CONST KEY = 'email';

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

        if( ! filter_var($value, FILTER_VALIDATE_EMAIL) ) {
            $this->error = __("must be an email address.", 'typerocket-domain');
        }

        return !$this->error;
    }
}