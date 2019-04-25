<?php


namespace TypeRocket\Http;


use TypeRocket\Controllers\Controller;
use TypeRocket\Utility\Str;

class Handler
{

    protected $handler;
    protected $action;
    protected $args;
    protected $hook;
    protected $middlewareGroup;
    protected $resource;
    protected $route;

    /**
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param string|Controller $handler
     * @return Handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return Handler
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Get the router arg
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function getArg($key, $default = null)
    {
        if( array_key_exists($key, $this->args) ) {
            $default = $this->args[$key];
        }

        return $default;
    }

    /**
     * @param array $args
     * @return Handler
     */
    public function setArgs($args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHook()
    {
        return $this->hook;
    }

    /**
     * @param bool $hook
     * @return Handler
     */
    public function setHook($hook)
    {
        $this->hook = $hook;

        return $this;
    }

    /**
     * @return array
     */
    public function getMiddlewareGroup()
    {
        return explode('|', $this->middlewareGroup);
    }

    /**
     * @param string|array $middlewareGroup
     * @return Handler
     */
    public function setMiddlewareGroup($middlewareGroup)
    {
        $group = (string) is_array($middlewareGroup) ? implode('|', $middlewareGroup) : $middlewareGroup;
        $this->middlewareGroup = strtolower($group);

        return $this;
    }

    /**
     * @param null $type
     * @return string
     */
    public function getResource($type = null)
    {
        switch($type) {
            case 'camel' :
            case 'camelize' :
                return Str::camelize( $this->resource );
            case 'lower' :
            case 'lowercase' :
                return strtolower( $this->resource );
        }

        return $this->resource;
    }

    /**
     * @param mixed $resource
     * @return Handler
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param mixed $route
     * @return mixed
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $route;
    }

}