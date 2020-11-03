<?php
namespace TypeRocket\Http;

use TypeRocket\Controllers\Controller;
use TypeRocket\Core\Resolver;

/**
 * Class Router
 *
 * Run proper controller based on request.
 *
 * @package TypeRocket\Http\Middleware
 */
class ControllerContainer
{
    /** @var Request  */
    protected $request;
    /** @var Response  */
    protected $response;
    /** @var Handler  */
    protected $handler;
    /** @var Controller  */
    protected $controller;

    /**
     * Router constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param Handler $handler
     * @throws \Exception
     */
    public function __construct( Request $request, Response $response, Handler $handler )
    {
        $this->request = $request;
        $this->response = $response;
        $this->handler = $handler;
        $this->controller = $this->handler->getController();
    }

    /**
     * Handle routing to controller
     * @throws \Exception
     */
    public function handle() {
        $returned = (new Resolver)->resolveCallable($this->controller, $this->handler->getArgs());
        $this->response->setReturn($returned);
    }

    /**
     * Get the middleware group
     *
     * @return array ['group_name', 'group_name']
     */
    public function getMiddlewareGroups()
    {
        $groups = [];
        $action = null;
        $middleware = null;

        if( is_array($this->controller) && method_exists($this->controller[0], 'getMiddleware') ) {
            /** @var Controller|object $controller */
            $controller = $this->controller[0];
            $middleware = $controller->getMiddleware();
            $action = $this->controller[1];
        }

        if(!$middleware) {
            return $groups;
        }

        foreach ($middleware as $set) {
            $use = null;

            if( ! $set['except'] && ! $set['only'] ) {
                $use = $set['group'];
            }

            if ($set['except'] && ! in_array($action, is_array($set['except']) ? $set['except'] : [$set['except']])) {
                $use = $set['group'];
            }

            if ($set['only'] && in_array($action, is_array($set['only']) ? $set['only'] : [$set['only']])) {
                $use = $set['group'];
            }

            if($use) {
                $groups[] = $use;
            }
        }

        return $groups;
    }

    /**
     * Response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

}