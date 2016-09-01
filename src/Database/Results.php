<?php
namespace TypeRocket\Database;

use TypeRocket\Models\Model;

class Results extends \ArrayObject
{

    public $class = null;
    public $property = 'properties';

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
     * Cast results
     *
     * Casting is normally to a Model class
     */
    public function castResults()
    {
        if( ! $this->class ) {
            return;
        }

        $array = $this->getArrayCopy();
        $models = [];
        if(!empty($array)) {
            foreach ( $array as $item ) {
                $model = (new $this->class);

                if( $model instanceof Model ) {
                    $model->castProperties( (array) $item );
                } else {
                    $property = $this->property;
                    $model->$property = (array) $item;
                }

                $models[] = $model;
            }
        }
        $this->exchangeArray( $models );
    }

    /**
     * Has Results
     *
     * @return bool
     */
    public function hasResults() {
        return $this->count() > 0 ? true : false;
    }
}