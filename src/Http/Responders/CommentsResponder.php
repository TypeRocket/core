<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Http\Handler;
use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;

class CommentsResponder extends Responder {

    /**
     * Respond to comments hook
     *
     * Create proper request and run through Kernel
     *
     * @param array $args
     */
    public function respond( $args ) {
        $controller = tr_app("Controllers\\CommentController");
        $controller  = apply_filters('tr_comments_responder_controller', $controller);
        $request = new Request('PUT', $this->hook);
        $response = (new Response())->blockFlash();

        $handler = (new Handler())
            ->setAction('update')
            ->setArgs($args)
            ->setHandler($controller)
            ->setHook($this->hook)
            ->setResource('comment')
            ->setMiddlewareGroups('comment');

        $this->runKernel($request, $response, $handler);

    }

}