<?php
namespace TypeRocket\Elements\Traits;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Html\Html;
use TypeRocket\Html\Tag;

trait Fieldable
{
    protected $fields = [];

    /**
     * Add Column
     *
     * @param mixed $field
     *
     * @return $this|array
     */
    public function field($field = null)
    {
        if(!$field) {
            return $this->fields;
        }

        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param mixed $fields
     *
     * @return $this
     */
    public function setFields(...$fields)
    {
        $this->fields = is_array($fields[0] ?? null) ? $fields[0] : $fields;

        return $this;
    }

    /**
     * Get From elements string from array
     *
     * @return string
     */
    public function getFieldsString()
    {
        $html = '';

        /** @var Field|CloneFields|string $field */
        foreach ($this->fields as $field) {
            if(is_string($field)) {
                $html .= $field;
            }
            elseif($field instanceof Tag || $field instanceof Html) {
                $html .= $field;
            }
            else {
                if(method_exists($field, 'cloneToForm')) {
                    $html .= $field->cloneToForm($this);
                }
            }

        }

        return $html;
    }
}