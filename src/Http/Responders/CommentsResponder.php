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
     * @param $commentId
     */
    public function respond( $commentId ) {

        if( ! is_array($commentId) ) {
            $commentId = [ 'id' => $commentId ];
        }

        $request = new Request('comments', 'PUT', $commentId, 'update');
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);

    }

}