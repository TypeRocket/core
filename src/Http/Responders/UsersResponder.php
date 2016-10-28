<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;

class UsersResponder extends Responder {

    /**
     * Respond to user hook
     *
     * Create proper request and run through Kernel
     *
     * @param $args
     */
    public function respond( $args ) {

        $request = new Request('user', 'PUT', $args, 'update', $this->hook);
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);
    }

}