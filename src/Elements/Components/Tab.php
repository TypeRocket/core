<?php
namespace TypeRocket\Elements\Components;

use TypeRocket\Elements\BaseForm;
use TypeRocket\Elements\Tabs;
use TypeRocket\Elements\Traits\CloneFields;
use TypeRocket\Elements\Traits\Fieldable;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Sanitize;

class Tab
{
    use CloneFields, Fieldable;

    protected $id;
    protected $accessor;
    protected $title;
    protected $active = false;
    protected $description;
    protected $callback;
    protected $icon;
    protected $fieldset = [];
    protected $url;

    /** @var Tabs|Tab */
    protected $ref;

    public function __construct($title, $icon = null, ...$arg)
    {
        $this->id = Helper::hash();
        $this->accessor = Sanitize::underscore($title);

        $this->setTitle($title);
        $this->setIcon($icon);

        if($arg) {
            $this->configure($arg);
        }
    }

    /**
     * @param Tabs|Tab $ref
     *
     * @return Tab
     */
    public function setReference($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Set Active
     *
     * @param bool $bool
     *
     * @return Tab
     */
    public function setActive($bool = true)
    {
        $this->active = $bool;

        return $this;
    }

    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAccessor()
    {
        return $this->accessor;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     *
     * @return Tab
     */
    public function setTitle($title)
    {
        $this->title = esc_html($title);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param mixed $callback
     *
     * @return Tab
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @param string $title
     * @param string $description
     * @param array $arg
     *
     * @return Tab
     */
    public function fieldset($title, $description, ...$arg)
    {
        $this->fieldset[] = new Fieldset($title, $description, ...$arg);

        return $this;
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
            $this->apply($item);

            if($item instanceof BaseForm) {
                $form = $item;
            }
        }

        if($form) {
            $this->configureToForm($form);
        }

        return $this;
    }

    /**
     * @param array|callable|Tabs|Fieldset $arg
     *
     * @return $this
     */
    public function apply($arg)
    {
        if(is_callable($arg)) {
            $this->callback = $arg;
        } elseif(is_array($arg)) {
            $this->fields = array_merge($this->fields, $arg);
        } elseif($arg instanceof Tabs) {
            $this->fields[] = $arg;
        } else {
            $this->fieldset[] = $arg;
        }

        return $this;
    }

    /**
     * @return null|array
     */
    public function getFieldset()
    {
        return $this->fieldset;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     *
     * @return Tab
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     *
     * @return Tab
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Clone Tabs
     */
    public function __clone() {
        $this->id = Helper::hash();

        if($this->fieldset) {
            foreach ($this->fieldset as $i => $set) {
                $this->fieldset[$i] = clone $set;
            }
        }

        $this->cloneFields();
    }

    /**
     * @param $form
     */
    public function afterCloneElementsToForm($form) {}

    /**
     * @param string $description
     *
     * @return Tab
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

}