<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Database\Query;
use TypeRocket\Utility\Validator;

class UniqueModelValidator extends ValidatorRule
{
    public CONST KEY = 'unique';

    public function validate() : bool
    {
        $result = null;
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
        $modelClass = $validator->getModelClass();

        if( $modelClass && ! $option3) {
            /** @var \TypeRocket\Models\Model $model */
            $model = new $modelClass;
            $model->where($option, $value);

            if($option2) {
                $model->where($model->getIdColumn(), '!=', $option2);
            }

            $result = $model->first();
        } elseif( $option3 || ( ! $modelClass && $option2 ) ) {
            [$table, $idColumn] = array_pad(explode('@', $option2, 2), 2, null);
            $query = (new Query)->table($table)->where($option, $value);

            if($idColumn && $option3) {
                $query->where($idColumn, '!=', $option3);
            }

            $result = $query->first();
        }

        if($result) {
            $this->error = __('is taken.','typerocket-domain');
        }

        return !$this->error;
    }
}