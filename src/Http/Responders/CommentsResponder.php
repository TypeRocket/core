<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Http\Request;

class CommentsResponder extends Responder {

    /**
     * Respond to comments hook
     *
     * Create proper request and run through Kernel
     *
     * @param array $args
     *
     * @throws \Exception
     */
    public function respond( $args ) {
        $controller = tr_app_class("Controllers\\CommentController");
        $controller  = apply_filters('tr_comments_responder_controller', $controller);
        $response = tr_response()->blockFlash();

        $this->handler
            ->setArgs($args)
            ->setController([new $controller, 'update'])
            ->setMiddlewareGroups(['comment']);

        $this->runKernel(new Request, $response, $this->handler);

    }

}