<?php
namespace TypeRocket\Http\Middleware;

use TypeRocket\Http\Handler;
use \TypeRocket\Http\Response;
use \TypeRocket\Http\Request;

abstract class Middleware
{

    /** @var null|Middleware $middleware */
    protected $next = null;
    protected $request = null;
    protected $response = null;
    protected $handler = null;

    public function __construct( Request $request, Response $response, $middleware = null, Handler $handler)
    {
    	$this->next = $middleware;
    	$this->request = $request;
    	$this->response = $response;
    	$this->handler = $handler;
        $this->init();
    }

    public function init() {

        return $this;
    }

    abstract public function handle();
}