<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;
use TypeRocket\Utility\Str;

class UsersResponder extends Responder {

    /**
     * Respond to user hook
     *
     * Create proper request and run through Kernel
     *
     * @param $args
     */
    public function respond( $args ) {
        $controller = tr_app("Controllers\\UserController");
        $controller  = apply_filters('tr_users_responder_controller', $controller);
        $request = new Request('user', 'PUT', $args, 'update', $this->hook, $controller);
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);
    }

}