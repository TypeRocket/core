<?php
namespace TypeRocket\Http\Middleware;

use TypeRocket\Http\Handler;
use TypeRocket\Http\Response;
use TypeRocket\Http\Request;

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
    /** @var Handler|null  */
    private $hook = false;

    public function __construct( Request $request, Response $response, $middleware = null, Handler $handler = null )
    {
    	$this->next = $middleware;
    	$this->request = $request;
    	$this->response = $response;
    	$this->handler = $handler;
    	$this->hook = $handler->getHook();
        $this->init();
    }

    public function isHook()
    {
        return $this->hook;
    }

    public function init() {

        return $this;
    }

    abstract public function handle();
}