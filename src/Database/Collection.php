<?php

namespace TypeRocket\Database;


class Results extends \ArrayObject
{
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
}