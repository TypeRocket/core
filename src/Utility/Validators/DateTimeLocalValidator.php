<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

/**
 * HTML5 datetime-local validation rule
 */
class DateTimeLocalValidator extends ValidatorRule
{
    public CONST KEY = 'datetime-local';

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

        if( !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}$/", trim($value)) ) {
            $this->error = __("must be local date time.",'typerocket-domain');
        }

        return !$this->error;
    }
}