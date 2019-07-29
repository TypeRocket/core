<?php
namespace TypeRocket\Http;

use ReflectionMethod;
use TypeRocket\Controllers\Controller;
use TypeRocket\Core\Config;
use TypeRocket\Core\Resolver;
use TypeRocket\Models\Model;

/**
 * Class Router
 *
 * Run proper controller based on request.
 *
 * @package TypeRocket\Http\Middleware
 */
class Router
{
    /** @var Request  */
    protected $request;
    /** @var Response  */
    protected $response;
    /** @var Handler  */
    protected $handler;

    /** @var Controller  */
    protected $controller;
    /** @var string  */
    protected $action;
    /** @var string  */
    protected $resource;
    /** @var array */
    public $middleware = [];

    /** @var mixed */
    public $returned = [];

    /**
     * Router constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param Handler $handler
     */
    public function __construct( Request $request, Response $response, Handler $handler )
    {
        $this->request = $request;
        $this->response = $response;
        $this->handler = $handler;

        $this->action = $this->handler->getAction();
        $this->resource = $this->handler->getResource('camel');

        $caller = $this->handler->getHandler() ?? tr_app("Controllers\\{$this->resource}Controller");

        if (!is_object($caller) && class_exists($caller)) {
            $caller = new $caller($this->request, $this->response);
        }

        if ( !$this->validController($caller) ) {
            $class = is_string($caller) ? $caller : '\\' . get_class($caller);
            $this->response->exitServerError("Invalid Controller Action: {$this->action}@{$this->resource}:{$class}");
        }

        $this->controller = $caller;
        $this->middleware = $this->controller->getMiddleware();
    }

    /**
     * Handle routing to controller
     * @throws \Exception
     */
    public function handle() {
        $action = $this->action;
        $controller = $this->controller;
        $params = (new ReflectionMethod($controller, $action))->getParameters();

        if( $params ) {

            $args = [];
            $vars = $this->handler->getArgs();

            foreach ($params as $index => $param ) {
                $varName = $param->getName();
                $class = $param->getClass();
                if ( $class ) {

                    $instance = (new Resolver)->resolve( $param->getClass()->name );

                    if( $instance instanceof Model ) {
                        $injectionColumn = $instance->getRouterInjectionColumn();
                        if( isset($vars[ $injectionColumn ]) ) {
                            $instance = $instance->findFirstWhereOrDie($injectionColumn, $vars[ $injectionColumn ] );
                        }
                    }

                    $args[$index] = $instance;
                } elseif( isset($vars[$varName]) ) {
                    $args[$index] = $vars[$varName];
                } else {
                    $args[$index] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                }
            }

            $this->returned = call_user_func_array( [$controller, $action], $args );
        } else {
            $this->returned = call_user_func( [$controller, $action] );
        }
    }

    /**
     * Get the middleware group
     *
     * @return array
     */
    public function getMiddlewareGroups() {
        $groups = [];
        $action = $this->action;

        foreach ($this->middleware as $group ) {
            if (array_key_exists('group', $group)) {
                $use = null;

                if( ! array_key_exists('except', $group) && ! array_key_exists('only', $group) ) {
                    $use = $group['group'];
                }

                if (array_key_exists('except', $group) && ! in_array($action, $group['except'])) {
                    $use = $group['group'];
                }

                if (array_key_exists('only', $group) && in_array($action, $group['only'])) {
                    $use = $group['group'];
                }

                if($use) {
                    $groups[] = $use;
                }
            }
        }

        return $groups;
    }

    /**
     * Validate Controller
     *
     * @param string $caller
     * @return bool
     */
    public function validController($caller)
    {
        if ($caller instanceof Controller && method_exists($caller, $this->action)) {
            return true;
        }

        return false;
    }

}