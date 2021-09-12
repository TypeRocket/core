<?php
namespace TypeRocket\Elements;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Traits\Attributes;
use TypeRocket\Elements\Traits\CloneFields;
use TypeRocket\Elements\Traits\DisplayPermissions;
use TypeRocket\Elements\Traits\Fieldable;
use TypeRocket\Html\Html;

class FieldColumn
{
    use Attributes, CloneFields, Fieldable, DisplayPermissions;

    protected $form;
    protected $dots;
    protected $contextRoot;

    /**
     * Get fields as row
     *
     * Array of fields or args of fields
     *
     * @param array|Field $fields
     */
    public function __construct( ...$fields )
    {
        $this->setFields(...$fields);
    }

    /**
     * Get Context ID
     *
     * @return string
     */
    public function getContextId()
    {
        return trim($this->contextRoot . '.' . $this->dots, '.') . '.-row-column';
    }

    /**
     * Return Fields as String in Row
     *
     * @return string
     */
    public function __toString()
    {
        if(!$this->canDisplay()) {
            return '';
        }

        $html = '';

        foreach( $this->fields as $field) {
            $html .= $field;
        }

        $this->attrClass('tr-control-row-column');

        return Html::div($this->attr(), $html)->getString();
    }
}