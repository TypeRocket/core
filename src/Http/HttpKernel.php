<?php
namespace TypeRocket\Http;

abstract class HttpKernel
{
    /** @var Request  */
    protected $request;
    /** @var Response  */
    protected $response;
    /** @var Handler */
    protected $handler;
    /** @var ControllerContainer */
    protected $controller;
    /** @var array  */
    protected $middleware = [];

    /**
     * Handle Middleware
     *
     * Run through global and resource level middleware.
     *
     * @param Request $request
     * @param Response $response
     * @param Handler $handler
     */
    public function __construct(Request $request, Response $response, Handler $handler)
    {
        $this->response = $response;
        $this->request = $request;
        $this->handler = $handler;
        do_action('typerocket_kernel', $this);
    }

    /**
     * Run Kernel
     * @throws \Exception
     */
    public function run()
    {
        $this->controller = new ControllerContainer($this->request, $this->response, $this->handler);
        $stack = new Stack( $this, $this->compileMiddleware() );
        $stack->handle($this->request, $this->response, $this->controller, $this->handler);
    }

    /**
     * Compile middleware from controller, router and kernel
     */
    public function compileMiddleware() : array
    {
        $stacks = [];

        // Route middleware
        $route = $this->handler->getRoute();
        if(!empty($route) && $route->middleware) {

            if(!is_array($route->middleware)) {
                $route->middleware = [$route->middleware];
            }

            $routeMiddleware = [];
            foreach ($route->middleware as $m) {
                if(is_string($m) && !empty($this->middleware[$m])) {
                    $routeMiddleware = array_merge($routeMiddleware, $this->middleware[$m] ?? []);
                } else {
                    $routeMiddleware[] = $m;
                }
            }

            $stacks[] = $routeMiddleware;
        }

        // Handler middleware
        $groups = array_filter( $this->handler->getMiddlewareGroups() );
        foreach ($groups as $group) {
            if($group && !empty($this->middleware[$group])) {
                $stacks[] = $this->middleware[$group];
                break; // Take the first group only
            }
        }

        $handlerMiddleware = [];
        foreach ($this->handler->getMiddleware() as $m) {
            if(is_string($m) && !empty($this->middleware[$m])) {
                $handlerMiddleware = array_merge($handlerMiddleware, $this->middleware[$m] ?? []);
            } else {
                $handlerMiddleware[] = $m;
            }
        }
        $stacks[] = $handlerMiddleware;

        // Controller middleware
        $controllerMiddleware = [];
        $groups = $this->controller->getMiddlewareGroups();
        foreach( $groups as $group ) {
            if(!is_array($group)) {
                $group = [$group];
            }

            foreach ($group as $g) {
                if(is_string($g) && !empty($this->middleware[$g])) {
                    $controllerMiddleware = array_merge($controllerMiddleware, $this->middleware[$g] ?? []);
                } else {
                    $controllerMiddleware[] = $g;
                }
            }
        }

        if( !empty($controllerMiddleware) ) {
            $stacks[] = $controllerMiddleware;
        }

        // Global middleware
        $globalGroup = $this->handler->getHook() ? 'hooks' : 'http';
        $stacks[] = $this->middleware[$globalGroup];

        // Compile stacks
        $middleware = call_user_func_array('array_merge', $stacks);
        $middleware = array_reverse( array_unique($middleware) );

        return apply_filters('typerocket_middleware', $middleware, $globalGroup);
    }

}