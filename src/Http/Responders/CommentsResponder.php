<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;

class CommentsResponder extends Responder {

    /**
     * Respond to comments hook
     *
     * Create proper request and run through Kernel
     *
     * @param $args
     */
    public function respond( $args ) {

        $request = new Request('comment', 'PUT', $args, 'update', $this->hook);
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);

    }

}