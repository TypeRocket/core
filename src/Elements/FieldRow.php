<?php
namespace TypeRocket\Elements;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Traits\Attributes;
use TypeRocket\Elements\Traits\CloneFields;
use TypeRocket\Elements\Traits\Conditional;
use TypeRocket\Elements\Traits\DisplayPermissions;
use TypeRocket\Elements\Traits\Fieldable;
use TypeRocket\Html\Html;

class FieldRow
{
    use Attributes, CloneFields, Fieldable, Conditional, DisplayPermissions;

    protected $fields = [];
    protected $form;
    protected $dots;
    protected $contextRoot;
    protected $hasColumns = false;
    protected $title = '';

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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get Context ID
     *
     * @return string
     */
    public function getContextId()
    {
        return trim($this->contextRoot . '.' . $this->dots, '.') . '.-row';
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

        if($this->title) {
            $html .= (string) Html::h4(['class' => 'tr-field-control-title'], $this->title);
        }

        foreach( $this->fields as $field) {
        	if( $field instanceof FieldColumn ) {
                $this->hasColumns = true;
                $html .= $field;
            } else {
                $html .= $field;
            }
        }

        $this->attrClass('tr-control-row tr-divide');
        $with = $this->getContextId() ? ['data-tr-context' => $this->getContextId()] : [];

        return Html::div($this->getAttributes($with + $this->getConditionalAttribute(true)), $html)->getString();
    }

    /**
     * Make Column
     *
     * @param mixed ...$fields
     *
     * @return FieldColumn
     */
    public function column(...$fields)
    {
        $column = (new FieldColumn(...$fields))->configureToForm($this->form);
        $this->fields[] = $column;

        return $column;
    }

    /**
     * Add Column & Return Row
     *
     * @param mixed ...$fields
     *
     * @return FieldRow
     */
    public function withColumn(...$fields)
    {
        $this->column(...$fields);

        return $this;
    }

    /**
     * Set Title
     *
     * @param string $title
     *
     * @return FieldRow $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

}