<?php
namespace TypeRocket\Services;

abstract class Service
{
    protected $singleton = true;
    public const ALIAS = null;

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
        return static::ALIAS;
    }
}