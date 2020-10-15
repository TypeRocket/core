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
        $controller = \TypeRocket\Utility\Helper::appNamespace("Controllers\\CommentController");
        $controller  = apply_filters('typerocket_comments_responder_controller', $controller);
        $response = \TypeRocket\Http\Response::getFromContainer()->blockFlash();

        $this->handler
            ->setArgs($args)
            ->setController([new $controller, 'update'])
            ->setMiddlewareGroups(['comment']);

        $this->runKernel(new Request, $response, $this->handler);

    }

}