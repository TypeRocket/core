<?php

namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Arr;
use TypeRocket\Utility\Data;
use TypeRocket\Utility\Str;
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

        $opts = explode('/', $option);

        if(is_array($value)) {
            $value = array_filter($value, function($v) {
                return isset($v);
            });
        }

        $allow_zero = in_array('allow_zero', $opts);
        $strong = in_array('strong', $opts);
        $weak = in_array('weak', $opts) && (is_null($value) || Arr::isEmptyArray($value));

        if($strong) {
            $value = is_array($value) ? Arr::mapDeep('trim', $value) : trim($value);
        }

        if($allow_zero) {
            $empty = Data::emptyOrBlankRecursive( $value );
        } else {
            $empty = Data::emptyRecursive($value);
        }

        if( !$weak && $empty ) {
            $this->error = __('is required.','typerocket-domain');
        }

        return !$this->error;
    }
}