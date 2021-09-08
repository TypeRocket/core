<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Data;
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
         * @var $subfields
         * @var $value
         * @var $type
         * @var Validator $validator
         */
        extract($this->args);

        if(is_array($subfields)) {
            $failed = false;

            foreach ($subfields as $field) {
                $failed = ! is_numeric(Data::walk($field, $value));
                if($failed) {
                    break;
                }
            }

        } else {
            $failed = ! is_numeric($value);
        }

        if( $failed ) {
            $this->error = __("must be a numeric value.",'typerocket-domain');
        }

        return !$this->error;
    }
}