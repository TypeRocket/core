<?php

namespace TypeRocket\Http;

class Stack
{
    protected $middleware;

    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }

    public function handle($request, $response, $client)
    {
        foreach($this->middleware as $class) {
            $client = new $class($request, $response, $client);
        }

        $client->handle();

        return $this;
    }
}