<?php
namespace TypeRocket\Http;

use TypeRocket\Controllers\Controller;
use TypeRocket\Utility\Validator;

/**
 * Class Router
 *
 * Run proper controller based on request.
 *
 * @package TypeRocket\Http\Middleware
 */
class Router
{
    public $returned = [];
    protected $request = null;
    protected $response = null;
    /** @var Controller  */
    protected $controller;
    public $middleware = [];
    public $action;
    public $item_id;

    /**
     * Router constructor.
     *
     * @param \TypeRocket\Http\Request $request
     * @param \TypeRocket\Http\Response $response
     * @param string $action_method
     */
    public function __construct( Request $request, Response $response, $action_method = 'GET' )
    {
        $this->request = $request;
        $this->response = $response;
        $this->action = $this->getAction( $action_method );
        $resource = ucfirst( $this->request->getResource() );
        $controller  = "\\" . TR_APP_NAMESPACE . "\\Controllers\\{$resource}Controller";

        if ( class_exists( $controller ) ) {
            $this->controller = $controller = new $controller( $this->request, $this->response);

            if ( ! $controller instanceof Controller || ! method_exists( $controller, $this->action ) ) {
                $this->response->setMessage('Something went wrong');
                $this->response->exitAny(405);
            }

            $this->item_id    = $this->request->getResourceId();
            $this->middleware = $this->controller->getMiddleware();

        } else {
            wp_die('Missing controller: ' . $controller );
        }
    }

    /**
     * Handle routing to controller
     */
    public function handle() {
        $action = $this->action;
        $controller = $this->controller;
        $params = (new \ReflectionMethod($controller, $this->action))->getParameters();

        if( $params ) {

            $args = [];
            $vars = Routes::$vars;

            if( !empty($vars) ) {
                foreach ($params as $index => $param ) {
                    if ( isset($vars[$param->getName()]) &&  $param->hasType() ) {
                        $paramType = $param->getType();
                        if( $paramType == 'Fields' || $paramType == Fields::class ) {
                            $args[$index] = new Fields( $this->request->getFields() );
                        }
                    } elseif( isset($vars[$param->getName()]) ) {
                        $args[$index] = $vars[$param->getName()];
                    }
                }
            } else {
                $args[] = $this->item_id;
            }

            $this->returned = call_user_func_array( [$controller, $action], $args );
        } else {
            $this->returned = $controller->$action();
        }
    }

    /**
     * Get the middleware group
     *
     * @return array
     */
    public function getMiddlewareGroups() {
        $groups = [];

        foreach ($this->middleware as $group ) {
            if (array_key_exists('group', $group)) {
                $use = null;

                if( ! array_key_exists('except', $group) && ! array_key_exists('only', $group) ) {
                    $use = $group['group'];
                }

                if (array_key_exists('except', $group) && ! in_array($this->action, $group['except'])) {
                    $use = $group['group'];
                }

                if (array_key_exists('only', $group) && in_array($this->action, $group['only'])) {
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
     * Get the controller action to call
     *
     * @param string $action_method
     *
     * @return null|string
     */
    protected function getAction( $action_method = 'GET' ) {
        $request = $this->request;

        $method = $request->getMethod();
        $action = null;
        switch ( $request->getAction() ) {
            case 'add' :
                if( $method == 'POST' ) {
                    $action = 'create';
                } else {
                    $action = 'add';
                }
                break;
            case 'create' :
                if( $method == 'POST' ) {
                    $action = 'create';
                }
                break;
            case 'edit' :
                if( $method == 'PUT' ) {
                    $action = 'update';
                } else {
                    $action = 'edit';
                }
                break;
            case 'update' :
                if( $method == 'PUT' ) {
                    $action = 'update';
                }
                break;
            case 'delete' :
                if( $method == 'DELETE' ) {
                    $action = 'destroy';
                } else {
                    $action = 'delete';
                }
                break;
            case 'index' :
                if( $method == 'GET' ) {
                    $action = 'index';
                }
                break;
            case 'show' :
                if( $method == 'GET' ) {
                    $action = 'show';
                }
                break;
            default :
                if($action_method == $method ) {
                    $action = $request->getAction();
                }
                break;
        }

        return $action;
    }

}