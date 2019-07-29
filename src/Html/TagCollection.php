<?php
namespace TypeRocket\Html;

class TagCollection extends \ArrayObject
{
    /**
     * Add item to top of collection
     *
     * @param string $value
     */
    public function prepend( $value )
    {
        $array = $this->getArrayCopy();
        array_unshift( $array, $value );
        $this->exchangeArray( $array );
    }
}