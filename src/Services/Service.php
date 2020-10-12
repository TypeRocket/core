<?php
namespace TypeRocket\Services;

abstract class Service
{
    protected $singleton = true;
    protected $alias = null;

    /**
     * @return $this
     */
    public function register() : Service
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function isSingleton()
    {
        return $this->singleton;
    }

    /**
     * @return null
     */
    public function alias()
    {
        return $this->alias;
    }
}