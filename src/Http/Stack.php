<?php
namespace TypeRocket\Http;

use TypeRocket\Http\Middleware\Middleware;

class Stack
{
    /** @var array  */
    protected $middleware = [];
    /** @var HttpKernel  */
    protected $kernel;

    /**
     * Stack Constructor
     *
     * @param HttpKernel $kernel
     * @param array $middleware
     */
    public function __construct(HttpKernel $kernel, array $middleware = null)
    {
        $this->kernel = $kernel;
        $this->setMiddleware($middleware);
    }

    /**
     * Handle
     *
     * @param Request $request
     * @param Response $response
     * @param ControllerContainer $client
     * @param mixed $handler
     *
     * @throws \Exception
     */
    public function handle($request, $response, $client, $handler)
    {
        foreach($this->middleware as $class) {
            /** @var Middleware $client */
            $client = new $class($request, $response, $client, $handler);
        }

        $client->handle();
    }

    /**
     * Set Middleware
     *
     * @param array $middleware
     */
    public function setMiddleware(array $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * Get Middleware
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Get Kernel
     *
     * @return HttpKernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }
}