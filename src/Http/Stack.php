<?php

namespace TypeRocket\Http;

use TypeRocket\Http\Middleware\Middleware;

class Stack
{
    protected $middleware;

    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }

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