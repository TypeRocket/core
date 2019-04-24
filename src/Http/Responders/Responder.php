<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;

abstract class Responder
{
    /** @var \TypeRocket\Http\Kernel */
    public $kernel;
    public $hook = false;

    /**
     * Run the Kernel
     *
     * @param Request $request
     * @param Response $response
     * @param string $type
     * @param null $action_method
     * @param null|\TypeRocket\Http\Route $route
     */
    public function runKernel(Request $request, Response $response, $type = 'hookGlobal', $action_method = null, $route = null )
    {
        $Kernel = tr_app("Http\\Kernel");
        $this->kernel = new $Kernel( $request, $response, $type, $action_method, $route);
        $this->kernel->runKernel();
    }
}