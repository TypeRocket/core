<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Http\Handler;
use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;

class UsersResponder extends Responder {

    /**
     * Respond to user hook
     *
     * Create proper request and run through Kernel
     *
     * @param array $args
     */
    public function respond( $args ) {
        $controller = tr_app("Controllers\\UserController");
        $controller  = apply_filters('tr_users_responder_controller', $controller);
        $request = new Request('PUT', $this->hook);
        $response = tr_response()->blockFlash();

        $handler = (new Handler())
            ->setAction('update')
            ->setArgs($args)
            ->setHandler($controller)
            ->setHook($this->hook)
            ->setResource('user')
            ->setMiddlewareGroups('user');

        $this->runKernel($request, $response, $handler);
    }

}