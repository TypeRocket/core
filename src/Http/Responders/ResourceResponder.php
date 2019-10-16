<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Http\Handler;
use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;

class ResourceResponder extends Responder
{

    protected $resource = null;
    protected $action = null;
    protected $route = null;
    protected $handler = null;
    protected $middlewareGroups = null;

    /**
     * Respond to custom requests
     *
     * Create proper request and run through Kernel
     *
     * @param array $args
     */
    public function respond( $args )
    {
        $request  = new Request(null, $this->hook, $this->rest, $this->custom);
        $response = tr_response();

        $handler = (new Handler())
            ->setAction($this->action)
            ->setArgs($args)
            ->setHandler($this->handler)
            ->setHook($this->hook)
            ->setResource($this->resource)
            ->setRoute($this->route)
            ->setRest($this->rest)
            ->setCustom($this->custom)
            ->setMiddlewareGroups($this->middlewareGroups ?? $this->resource);

        $this->runKernel($request, $response, $handler);
        tr_http_response($this->kernel->router->returned, $response);
    }

    /**
     * Set the resource use to construct the Request
     *
     * @param string $resource
     *
     * @return $this
     */
    public function setResource( $resource )
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Set the action
     *
     * @param string $action
     *
     * @return $this
     */
    public function setAction( $action ) {
        $this->action = $action;

        return $this;
    }

    /**
     * Set the route
     *
     * @param string $route
     *
     * @return $this
     */
    public function setRoute( $route ) {
        $this->route = $route;

        return $this;
    }

    /**
     * Set Handler
     *
     * @param string $handler
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Set Middleware Groups
     *
     * @param string|array $groups
     * @return $this
     */
    public function setMiddlewareGroups($groups)
    {
        $groups = (string) is_array($groups) ? implode('|', array_filter($groups)) : $groups;
        $this->middlewareGroups = strtolower($groups);

        return $this;
    }

    /**
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return explode('|', $this->middlewareGroups);
    }

    /**
     * Set Rest
     *
     * @param bool $rest
     * @return ResourceResponder
     */
    public function setRest($rest)
    {
        $this->rest = $rest;
        return $this;
    }

    /**
     * Set Custom
     *
     * @param bool $custom
     * @return $this
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;

        return $this;
    }

}
