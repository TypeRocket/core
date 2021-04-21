<?php
namespace TypeRocket\Register;

use TypeRocket\Utility\Sanitize;

trait Resourceful
{
    protected $templates = null;
    protected $resource = null;

    /**
     * Set the Registrable ID for WordPress to use. Don't use reserved names.
     *
     * @param string $id set the ID
     * @param boolean $resource update the resource binding
     * @param int $max
     *
     * @return $this
     */
    public function setId($id, $resource = false)
    {
        $this->setRawId(Sanitize::underscore($id), $resource);

        return $this;
    }

    /**
     * Set the Registrable ID for WordPress to use. Don't use reserved names.
     *
     * @param string $id set the ID in raw form
     * @param boolean $resource update the resource binding
     *
     * @return $this
     */
    public function setRawId($id, $resource = false)
    {
        $length = $this->getMaxIdLength();
        $sub = substr($id, 0, $length);

        $this->id = $sub ?: $id;

        if($resource) {
            $this->resource['singular'] = $this->id;
        }

        return $this;
    }

    /**
     * Override Default Controller and Model
     *
     * @param string $controller_class
     * @return $this
     */
    public function setHandler($controller_class)
    {
        $this->resource['controller'] = $controller_class;

        return $this;
    }

    /**
     * Set Templates
     *
     * @param array $templates
     * @return $this
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * Get Templates
     *
     * @return null
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param string $name options: controller, singular, plural, model, obj
     *
     * @return null
     */
    public function getResource($name)
    {
        return $this->resource[$name] ?? null;
    }
}
