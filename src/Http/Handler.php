<?php
namespace TypeRocket\Http;

use TypeRocket\Core\Resolver;
use TypeRocket\Utility\Str;

class Handler
{
    protected $controller;
    protected $constructController;
    protected $args;
    protected $hook;
    protected $template;
    protected $middlewareGroups = [];
    protected $middleware = [];
    protected $route;

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
     * @param string $key
     * @param null|mixed $default
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
    public function setArgs($args) : Handler
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @return bool
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param bool $bool
     * @return Handler
     */
    public function setTemplate($bool = true) : Handler
    {
        $this->template = $bool;

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
     * @param bool $bool
     * @return Handler
     */
    public function setHook($bool = true) : Handler
    {
        $this->hook = $bool;

        return $this;
    }

    /**
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    /**
     * @param array $groups
     * @return Handler
     */
    public function setMiddlewareGroups(array $groups) : Handler
    {
        $this->middlewareGroups = $groups;

        return $this;
    }

    /**
     * @param array|string $middleware
     *
     * @return $this
     */
    public function setMiddleware($middleware)
    {
        $this->middleware = array_filter(is_array($middleware) ? $middleware : [$middleware]);

        return $this;
    }

    /**
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
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
     * @return $this
     */
    public function setRoute($route) : Handler
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Set Controller
     *
     * @param array|callable|string $controller
     * @param array $with associative array of constructor values
     *
     * @return Handler
     */
    public function setController($controller, array $with = []) : Handler
    {
        $provided = $controller;

        if ( !is_callable($controller) && is_string($controller) ) {
            [$action, $controller] = array_pad(explode('@', $controller), 2, null);
            $maybeController = \TypeRocket\Utility\Helper::controllerClass($controller, false);

            if( strpos($controller, "\\") === false && class_exists($maybeController) ) {
                $controller = $maybeController;
            }

            if(!class_exists($controller)) {
                wp_die('Invalid controller provided: ' . $provided);
            }

            $controller = [$controller, $action];
        }

        $this->controller = $controller;
        $this->constructController = $with;

        return $this;
    }

    /**
     * Get Controller
     *
     * @return array|callable
     * @throws \Exception
     */
    public function getController()
    {
        if(is_array($this->controller) && is_string($this->controller[0])) {

            /**
             * Is Resource
             *
             * A definition can start with an @. When it does load the
             * controller it belongs to from the app folder.
             */
            if($this->controller[0][0] === '@') {
                $resource = Str::camelize( substr($this->controller[0], 1) );
                $this->controller[0] = \TypeRocket\Utility\Helper::controllerClass($resource, false);
            }

            $this->controller[0] = (new Resolver)->resolve($this->controller[0], $this->constructController);
        }

        if(is_array($this->controller) && method_exists($this->controller[0], 'onHandlerAcquire')) {
            (new Resolver)->resolveCallable([$this->controller[0], 'onHandlerAcquire'], $this);
        }

        if(is_callable($this->controller, true)) {
            return $this->controller;
        }

        return null;
    }

}