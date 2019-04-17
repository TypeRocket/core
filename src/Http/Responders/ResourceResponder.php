<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Redirect;
use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;
use TypeRocket\Http\Routes;

class ResourceResponder extends Responder
{

    private $resource = null;
    private $action = null;
    private $route = null;
    private $actionMethod = null;
    private $handler = null;

    /**
     * Respond to custom requests
     *
     * Create proper request and run through Kernel
     *
     * @param $args
     */
    public function respond( $args )
    {
        $request  = new Request( $this->resource, null, $args, $this->action, $this->hook, $this->handler );
        $response = new Response();
        $this->runKernel($request, $response, 'resourceGlobal', $this->actionMethod, $this->route);
        tr_http_response($this->kernel->router->returned, $response);
    }

    /**
     * Set the resource use to construct the Request
     *
     * @param $resource
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
     * @param $action
     *
     * @return $this
     */
    public function setAction( $action ) {
        $this->action = $action;

        return $this;
    }

    /**
     * Set the action
     *
     * @param $action
     *
     * @return $this
     */
    public function setRoute( $route ) {
        $this->route = $route;

        return $this;
    }

    /**
     * Set the action method
     *
     * @param $action_method
     *
     * @return $this
     */
    public function setActionMethod( $action_method ) {
        $this->actionMethod = $action_method;

        return $this;
    }

    /**
     * Set Handler
     *
     * @param $handler
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

}
