<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Http\Request;

class UsersResponder extends Responder {

    /**
     * Respond to user hook
     *
     * Create proper request and run through Kernel
     *
     * @param array $args
     */
    public function respond( $args ) {
        $controller = tr_app_class("Controllers\\UserController");
        $controller  = apply_filters('tr_users_responder_controller', $controller);
        $response = tr_response()->blockFlash();

        $this->handler
            ->setArgs($args)
            ->setController([new $controller, 'update'])
            ->setMiddlewareGroups(['user']);

        $this->runKernel(new Request, $response, $this->handler);
    }

}