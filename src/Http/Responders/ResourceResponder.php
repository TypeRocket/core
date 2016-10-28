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
    private $actionMethod = null;

    /**
     * Respond to custom requests
     *
     * Create proper request and run through Kernel
     *
     * @param $args
     */
    public function respond( $args )
    {
        $request  = new Request( $this->resource, null, $args, $this->action, $this->hook );
        $response = new Response();
        $this->runKernel($request, $response, 'resourceGlobal', $this->actionMethod);
        $this->response( $this->kernel->router->returned , $response);
    }

    public function response($returned, Response $response)
    {
        status_header( $response->getStatus() );

        if( $returned && empty($_POST['_tr_ajax_request']) ) {

            if( $returned instanceof Redirect ) {
                $returned->now();
            }

            if( is_string($returned) ) {
                echo $returned;
                die();
            }

            Routes::resultsToJson( $returned );

        } else {
            wp_send_json( $response->getResponseArray() );
        }
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

}
