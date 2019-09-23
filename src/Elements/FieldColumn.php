<?php

namespace TypeRocket\Elements;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Traits\AttributesTrait;
use TypeRocket\Html\Generator;
use TypeRocket\Html\Tag;

class FieldColumn
{
    use AttributesTrait;

    public $fields = [];
    public $size = [];
    public $title = '';

    /**
     * Get fields as row
     *
     * Array of fields or args of fields
     *
     * @param array|Field $fields
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
        $fieldsHtml = $title = '';
        $class = "control-row-column";

        if($this->title) {
            $fieldsHtml .= (string) Tag::make('h4', ['class' => 'form-control-title'], $this->title);
        }

        foreach( $this->fields as $field) {
            if( $field instanceof Field ) {
                $fieldsHtml .= (string) $field;
            }
        }

        if($this->title) {
            $class .= ' control-row-column-has-title';
        }

        $this->appendStringToAttribute('class', $class);
        $html = ( new Generator() )->newElement('div', $this->getAttributes(), $fieldsHtml)->getString();

        return $html;
    }

    /**
     * Set Title
     *
     * @param string $title
     *
     * @return FieldColumn $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }
}