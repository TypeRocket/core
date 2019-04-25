<?php

namespace TypeRocket\Http\Rewrites;

use TypeRocket\Http\Request;
use TypeRocket\Http\Responders\ResourceResponder;

class Rest
{

    public $reserved_resources = [
        'option' => ['fallback_middleware_group' => null],
        'post' => ['fallback_middleware_group' => null],
        'page' => ['fallback_middleware_group' => 'post'],
        'category' => ['fallback_middleware_group' => 'term'],
        'tag' => ['fallback_middleware_group' => 'term'],
        'user' => ['fallback_middleware_group' => null],
        'comment' => ['fallback_middleware_group' => null],
    ];

    public function __construct()
    {
        if ( defined( 'TR_PATH' ) ) {
            $tr_resource = get_query_var( 'tr_json_controller', null );
            $tr_item_id  = get_query_var( 'tr_json_item', null );

            $tr_load = apply_filters( 'tr_rest_api_load', true, $tr_resource, $tr_item_id );
            if ($tr_load) {
                do_action('tr_rest_api_loaded', $this, $tr_resource, $tr_item_id);

                $request = new Request();
                if( $request->isPut() ) {
                    $action = 'update';
                } elseif( $request->isDelete() ) {
                    $action = 'destroy';
                } elseif( $request->isPost() ) {
                    $action = 'create';
                } else {
                    $action = 'showRest';
                }

                $fallback_middleware_group = $this->reserved_resources[$tr_resource]['fallback_middleware_group'] ?? null;

                (new ResourceResponder())
                    ->setAction($action)
                    ->setRest(true)
                    ->setResource($tr_resource)
                    ->setMiddlewareGroups([$tr_resource, $fallback_middleware_group])
                    ->respond($tr_item_id);
            }
        }

        status_header(404);
        die();
    }

}