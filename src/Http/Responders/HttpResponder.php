<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Http\Request;

class HttpResponder extends Responder
{

    /**
     * Respond to custom requests
     *
     * Create proper request and run through Kernel
     *
     * @param array $args
     */
    public function respond( $args )
    {
        $request  = new Request;
        $response = \TypeRocket\Http\Response::getFromContainer();
        $this->handler->setArgs($args);

        $this->runKernel($request, $response, $this->handler);
    }

}
