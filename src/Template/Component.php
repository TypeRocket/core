<?php
namespace TypeRocket\Template;

use TypeRocket\Elements\BaseForm;
use TypeRocket\Models\Model;

class Component
{
    protected $uuid;
    protected $form;
    protected $data;
    protected $title;
    protected $titleUnaltered;
    protected $registeredAs;
    protected $thumbnail;
    protected $cloneable = false;
    protected $nameable = false;

    public function __construct()
    {
        $this->uuid = bin2hex(random_bytes(16));
        $this->titleUnaltered = $this->title;
    }

    /**
     * Admin Fields
     */
    public function fields() { }

    /**
     * Render
     *
     * @var array $data component fields
     * @var array $info name, item_id, model, first_item, last_item, component_id, hash
     */
    public function render(array $data, array $info)
    {
        var_dump($data, $info);
    }

    /**
     * @return BaseForm|\App\Elements\Form|$this
     */
    public function form($form = null)
    {
        if(func_num_args() == 0) {
            return $this->form;
        }

        $this->form = $form;

        return $this;
    }

    /**
     * @param null|string $title
     *
     * @return $this|string
     */
    public function title($title = null)
    {
        if(func_num_args() == 0) {
            return $this->title ?? substr(strrchr(get_class($this), "\\"), 1);
        }

        $this->title = esc_attr($title);

        return $this;
    }

    /**
     * @return null|string
     */
    public function titleUnaltered()
    {
        return $this->titleUnaltered;
    }

    /**
     * @param null|string $url
     *
     * @return $this|string
     */
    public function thumbnail($url = null)
    {
        if(func_num_args() == 0) {
            return $this->thumbnail ?? \TypeRocket\Core\Config::get('urls.components') . '/' . $this->registeredAs() . '.png';
        }

        $this->thumbnail = $url;

        return $this;
    }

    /**
     * @param null|Model|array $data
     *
     * @return $this|null|Model|array
     */
    public function data($data = null)
    {
        if(func_num_args() == 0) {
            return $this->data;
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @param null|string $name
     *
     * @return $this|string
     */
    public function registeredAs($name = null)
    {
        if(func_num_args() == 0) {
            return $this->registeredAs;
        }

        $this->registeredAs = $name;

        return $this;
    }

    /**
     * Get a random id for this instance
     *
     * @return string
     */
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * @return bool
     */
    public function advanced()
    {
        return $this->advanced;
    }

    /**
     * @return mixed
     */
    public function feature($name)
    {
        if($name == 'nameable') {
            return '<div>' . $this->title() . '</div>';
        }

        return null;
    }
}