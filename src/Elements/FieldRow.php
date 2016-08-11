<?php

namespace TypeRocket\Elements;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Traits\AttributesTrait;
use TypeRocket\Html\Generator;

class FieldRow
{
    use AttributesTrait;

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

        $this->setAttribute('class', '');
        $this->fields = $fields;
        $this->size = count($fields);
    }

    /**
     * Return Fields as String in Row
     *
     * @return string
     */
    public function __toString()
    {
        $fieldsHtml = '';
        $class = "control-row-{$this->size}";

        foreach( $this->fields as $field) {
            if( $field instanceof Field ) {
                $fieldsHtml .= (string) $field;
            }
        }

        $this->appendStringToAttribute('class', $class);
        $html = ( new Generator() )->newElement('div', $this->getAttributes(), $fieldsHtml)->getString();

        return $html;
    }

}