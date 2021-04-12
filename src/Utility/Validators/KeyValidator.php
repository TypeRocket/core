<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Validator;

class KeyValidator extends ValidatorRule
{
    public CONST KEY = 'key';

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

        if( Sanitize::underscore($value) !== $value ) {
            $this->error = __("may only contain lowercase alphanumeric characters and underscores.",'typerocket-domain');
        }

        return !$this->error;
    }
}