<?php

namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

class RequiredValidator extends ValidatorRule
{
    public CONST KEY = 'required';

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
        $option = $option ?? null;

        $weak = $option === 'weak' && is_null($value);

        if( !$weak && empty( $value ) ) {
            $this->error = __('is required.','typerocket-domain');
        }

        return !$this->error;
    }
}