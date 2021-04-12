<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

class UrlValidator extends ValidatorRule
{
    public CONST KEY = 'url';

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

        if( ! filter_var($value, FILTER_VALIDATE_URL) ) {
            $this->error = __("must be at a URL.",'typerocket-domain');
        }

        return !$this->error;
    }
}