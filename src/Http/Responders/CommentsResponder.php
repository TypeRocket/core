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
        $controller = tr_app("Controllers\\CommentController");
        $controller  = apply_filters('tr_comments_responder_controller', $controller);
        $request = new Request('comment', 'PUT', $args, 'update', $this->hook, $controller);
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);

    }

}