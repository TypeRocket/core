<?php

namespace TypeRocket\Elements;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Utility\Buffer;

class FieldRow
{

    public $fields = [];
    public $size = [];

    /**
     * Get fields as row
     *
     * Array of fields or args of fields
     *
     * @param array|Field $fields
     *
     * @return FieldRow
     */
    public function __construct( $fields )
    {
        if( ! is_array( $fields) ) {
            $fields = func_get_args();
        }

        $this->size = count($fields);
    }

    /**
     * Return Fields as String in Row
     *
     * @return string
     */
    public function __toString()
    {
        $buffer = new Buffer();
        $buffer->startBuffer();
        echo "<div class=\"control-row-{$this->size}\" >";
        foreach( $this->fields as $field) {
            if( $field instanceof Field ) {
                echo $field;
            }
        }
        echo "</div>";

        return $buffer->getCurrent();
    }

}