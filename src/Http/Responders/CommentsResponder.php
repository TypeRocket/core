<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;
use TypeRocket\Utility\Str;

class CommentsResponder extends Responder {

    /**
     * Respond to comments hook
     *
     * Create proper request and run through Kernel
     *
     * @param $args
     */
    public function respond( $args ) {
        $resource = 'comment';
        $Resource = Str::camelize( $resource );
        $controller  = tr_app("Controllers\\{$Resource}Controller");

        $request = new Request($resource, 'PUT', $args, 'update', $this->hook, $controller);
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);

    }

}