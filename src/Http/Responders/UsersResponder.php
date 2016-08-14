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
     * @param $userId
     */
    public function respond( $userId ) {

        if( ! is_array($userId) ) {
            $userId = [ 'id' => $userId ];
        }

        $request = new Request('users', 'PUT', $userId, 'update');
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);
    }

}