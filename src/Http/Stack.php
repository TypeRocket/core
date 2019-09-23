<?php

namespace TypeRocket\Http;

use TypeRocket\Http\Middleware\Middleware;

class Stack
{
    protected $middleware;

    /**
     * Stack Constructor
     *
     * @param array $middleware
     */
    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * Handle
     *
     * @param Request $request
     * @param Response $response
     * @param Router $client
     * @param mixed $handler
     *
     * @return $this
     * @throws \Exception
     */
    public function handle($request, $response, $client, $handler)
    {
        foreach($this->middleware as $class) {
            /** @var Middleware $client
             */
            $client = new $class($request, $response, $client, $handler);
        }

        $client->handle();

        return $this;
    }
}