<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Http\Handler;
use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;

abstract class Responder
{
    /** @var \TypeRocket\Http\Kernel */
    public $kernel;
    public $hook = false;
    public $rest = false;
    public $custom = false;

    /**
     * Run the Kernel
     *
     * @param Request $request
     * @param Response $response
     * @param Handler $handler
     */
    public function runKernel(Request $request, Response $response, Handler $handler )
    {
        $Kernel = tr_app("Http\\Kernel");
        $this->kernel = new $Kernel( $request, $response, $handler);
        $this->kernel->runKernel();
    }
}