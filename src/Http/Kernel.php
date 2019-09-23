<?php
namespace TypeRocket\Http;

abstract class Kernel
{

    public $request;
    public $response;
    /** @var Handler */
    public $handler;

    /** @var Router */
    public $router;
    public $middleware = [];

    /**
     * Handle Middleware
     *
     * Run through global and resource level middleware.
     *
     * @param Request $request
     * @param Response $response
     * @param Handler $handler
     */
    public function __construct(Request $request, Response $response, Handler $handler) {
        $this->response = $response;
        $this->request = $request;
        $this->handler = $handler;
        do_action('tr_kernel', $this);
    }

    /**
     * Run Kernel
     */
    public function runKernel()
    {
        $groups = $this->handler->getMiddlewareGroups();

        if($this->handler->getRest()) {
            $groups[] = 'restApiFallback';
        }

        $resourceMiddleware = [];
        foreach ($groups as $group) {
            if($group && !empty($this->middleware[$group])) {
                $resourceMiddleware = $this->middleware[$group];
                break;
            }
        }

        if(!empty($this->route) && $this->route->middleware) {

            if(is_string($this->route->middleware)) {
                $this->route->middleware = $this->middleware[$this->route->middleware] ?? [];
            }

            $resourceMiddleware = array_merge($resourceMiddleware, $this->route->middleware);
        }

        $client = $this->router = new Router($this->request, $this->response, $this->handler);
        $middleware = $this->compileMiddleware($resourceMiddleware);

        (new Stack($middleware))->handle($this->request, $this->response, $client, $this->handler);
    }

    /**
     * Compile middleware from controller, router and kernel
     *
     * @param array $middleware
     *
     * @return mixed|void
     */
    public function compileMiddleware( $middleware ) {

        $routerWare = [];
        $groups = $this->router->getMiddlewareGroups();
        foreach( $groups as $group ) {
            $routerWare[] = $this->middleware[$group];
        }

        if( !empty($routerWare) ) {
            $routerWare = call_user_func_array('array_merge', $routerWare);
        }

        $globalMiddleware = $this->handler->getHook() ? 'hookGlobal' : 'resourceGlobal';

        $middleware = array_merge( $middleware, $this->middleware[$globalMiddleware], $routerWare);
        $middleware = array_reverse($middleware);
        return apply_filters('tr_kernel_middleware', $middleware, $this->request, $globalMiddleware);
    }

}