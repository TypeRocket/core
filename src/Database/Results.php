<?php
namespace TypeRocket\Database;

class Results extends \ArrayObject
{

    public $modelClass = null;

    /**
     * Add item to top of collection
     *
     * @param $value
     */
    public function prepend( $value )
    {
        $array = $this->getArrayCopy();
        array_unshift( $array, $value );
        $this->exchangeArray( $array );
    }

    /**
     * Cast results to Model
     */
    public function castResultsToModel()
    {
        if( ! $this->modelClass) {
            return;
        }

        $array = $this->getArrayCopy();
        $models = [];
        if(!empty($array)) {
            foreach ( $array as $item ) {
                $model = (new $this->modelClass);
                $model->properties = (array) $item;
                $models[] = $model;
            }
        }
        $this->exchangeArray( $models );
    }
}