<?php
namespace TypeRocket\Http\Middleware;

use TypeRocket\Http\Handler;
use \TypeRocket\Http\Response;
use \TypeRocket\Http\Request;

abstract class Middleware
{

    /** @var Middleware|null $middleware */
    protected $next = null;
    /** @var Request  */
    protected $request;
    /** @var Response  */
    protected $response;
    /** @var Handler|null  */
    protected $handler = null;

    public function __construct( Request $request, Response $response, $middleware = null, $handler = null )
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