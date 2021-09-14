<?php
namespace TypeRocket\Elements\Components;

use TypeRocket\Elements\BaseForm;
use TypeRocket\Elements\Traits\Attributes;
use TypeRocket\Elements\Traits\CloneFields;
use TypeRocket\Elements\Traits\Conditional;
use TypeRocket\Elements\Traits\DisplayPermissions;
use TypeRocket\Html\Html;

class Fieldset
{
    use CloneFields, Attributes, Conditional, DisplayPermissions;

    protected $title;
    protected $description;
    protected $callback;
    protected $dots;
    protected $contextRoot;
    protected $fields = [];

    /**
     * Fieldset constructor.
     *
     * @param string $title
     * @param string $description
     * @param mixed ...$arg
     */
    public function __construct($title, $description, ...$arg)
    {
        $this->setTitle($title);
        $this->setDescription($description);

        if($arg) {
            $this->configure($arg);
        }
    }

    /**
     * @param array|callable $arg
     *
     * @return $this
     */
    public function configure(array $arg)
    {
        $form = null;

        foreach ($arg as $item) {
            if(is_callable($item)) {
                $this->callback = $item;
            } elseif(is_array($item)) {
                $this->fields = array_merge($this->fields, $item);
            } elseif($item instanceof BaseForm) {
                $form = $item;
            } else {
                $this->fields[] = $item;
            }
        }

        if($form) {
            $this->configureToForm($form);
        }

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
     * @return Fieldset
     */
    public function setFields(...$fields)
    {
        $this->fields = is_array($fields[0]) ? $fields[0] : $fields;

        return $this;
    }

    /**
     * Set Title
     *
     * @param mixed $title
     *
     * @return Fieldset
     */
    public function setTitle($title)
    {
        $this->title = esc_html($title);;
        return $this;
    }

    /**
     * Get Context ID
     *
     * @return string
     */
    public function getContextId()
    {
        return trim($this->contextRoot . '.' . $this->dots, '.') . '.-fieldset';
    }

    /**
     * @param mixed $description
     *
     * @return Fieldset
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param mixed $callback
     *
     * @return Fieldset
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Clone Fieldset
     */
    public function __clone() {
        $this->cloneFields();
    }

    /**
     * @return string
     */
    public function getString() : string
    {
        return (string) $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if(!$this->canDisplay()) {return '';}
        $html = "<h3 class=\"tr-fieldset-group-title\">{$this->title}</h3>";
        $html .= "<p class=\"tr-fieldset-group-description\">{$this->description}</p>";
        $html .= '<fieldset class="tr-fieldset-group-content">';

        ob_start();
        if($this->callback) {
            call_user_func_array( $this->callback, [$this]);
        }

        if($this->fields) {
            foreach ($this->fields as $field) {
                echo $field;
            }
        }

        $html .= ob_get_clean();

        $html .= '</fieldset>';
        $this->attrClass('tr-fieldset-group');
        $with = $this->getContextId() ? ['data-tr-context' => $this->getContextId()] : [];

        return (string) Html::div($this->getAttributes($with + $this->getConditionalAttribute(true)))->nest($html);
    }
}