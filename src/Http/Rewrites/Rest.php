<?php

namespace TypeRocket\Http\Rewrites;

use TypeRocket\Http\Request;
use TypeRocket\Http\Responders\ResourceResponder;
use TypeRocket\Register\Registry;

class Rest
{

    public $responder;
    public $resource;
    public $action;
    public $item;
    public $middleware;
    public $handler;
    public $middleware_fallback;

    public function __construct()
    {
        if ( defined( 'TR_PATH' ) ) {
            $this->resource = get_query_var( 'tr_json_controller', null );
            $this->item  = (int) get_query_var( 'tr_json_item', null );
            $this->responder = (new ResourceResponder());

            $request = new Request();
            if( $request->isPut() ) {
                $this->action = 'update';
            } elseif( $request->isDelete() ) {
                $this->action = 'destroy';
            } elseif( $request->isPost() ) {
                $this->action = 'create';
            } else {
                $this->action = 'showRest';
            }

            if($obj = Registry::getPostTypeResource($this->resource)) {
                $this->middleware_fallback = 'post';
                $this->middleware = $this->resource;
                $this->handler = $obj[3];
            }
            elseif($obj = Registry::getTaxonomyResource($this->resource)) {
                $this->middleware_fallback = 'term';
                $this->middleware = $this->resource;
                $this->handler = $obj[3];
            } elseif($obj = Registry::getCustomResource($this->resource)) {
                $this->middleware_fallback = '';
                $this->middleware = $this->resource;
                $this->handler = $obj[3];
            }

            if ( apply_filters( 'tr_rest_api_load', true, $this->resource, $this->item ) ) {
                do_action('tr_rest_api_loaded', $this, $this->resource, $this->item);
                $this->responder
                    ->setAction($this->action)
                    ->setHandler($this->handler)
                    ->setRest(true)
                    ->setResource($this->resource)
                    ->setMiddlewareGroups([$this->middleware ?? $this->resource, $this->middleware_fallback])
                    ->respond(['id' => $this->item]);
            }
        }

        status_header(404);
        die();
    }

}