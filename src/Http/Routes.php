<?php

namespace TypeRocket\Http;

use TypeRocket\Http\Responders\ResourceResponder;
use TypeRocket\Template\View;

/**
 * Class Routes
 *
 * Store all the routes for TypeRocket Resources if there are any.
 *
 * @package TypeRocket\Http
 */
class Routes
{
    public static $routes = [];
    public $vars = [];
    public static $request;

    /**
     * Routes constructor.
     */
    public function __construct()
    {
        self::$request = new Request();

        if( ! is_admin() ) {
            add_filter('template_include', \Closure::bind(function( $template ) {
                $this->route();
                return $template;
            }, $this) );
        }

    }

    /**
     * Add Route
     *
     * @param $method
     * @param $path
     * @param $handler
     */
    public static function addRoute( $method, $path, $handler )
    {
        self::$routes[$method][$path] = $handler;
    }

    /**
     * Run route if there is a callback
     *
     * @param null $path
     * @param null $handle
     * @param null $wilds
     */
    private function runRoute($path = null, $handle = null, $wilds = null)
    {
        global $wp_query;

        $args = [$path, self::$request, $wilds];
        $this->vars = $wilds;

        if( ! str_ends('/', self::$request->getPath() ) && self::$request->getMethod() == 'GET' ) {
            wp_redirect( self::$request->getPath() . '/' );
            die();
        }

        if (is_callable($handle)) {
            $GLOBALS['error'] = '';
            $wp_query->is_404 = false;

            $returned = call_user_func_array($handle, $args);

            if( $returned instanceof View ) {
                $this->loadView();
            }

            if( $returned instanceof Redirect ) {
                $returned->now();
            }

            if( is_string($returned) ) {
                echo $returned;
                die();
            }

            if( is_array($returned) ) {
                wp_send_json($returned);
            }

        } else {
            list($action, $resource) = explode('@', $handle);
            $respond = new ResourceResponder();
            $respond->setResource( ucfirst($resource) );
            $respond->setAction( $action );
            $respond->setActionMethod( strtoupper( self::$request->getFormMethod() ) );
            $respond->respond( $this->vars );
            $this->loadView();
        }

        die();
    }

    /**
     *  Load the template for the front-end without globals
     */
    private function loadView() {
        add_filter('document_title_parts', function( $title ) {
            if( is_string(View::$title) ) {
                $title = [];
                $title['title'] = View::$title;
            } elseif ( is_array(View::$title) ) {
                $title = View::$title;
            }
            return $title;
        });

        extract( View::$data );
        /** @noinspection PhpIncludeInspection */
        include ( View::$view );
    }

    /**
     * Route request through registered routes if these is a match
     */
    public function route()
    {
        $requestPath = trim(self::$request->getPath(), '/');
        $routesRegistered = $this->getRegisteredRoutesByMethod( self::$request->getFormMethod() );
        $segmentsFromRequest = $this->getPathSegments( $requestPath );
        $args = null;
        $handle = null;

        foreach ($routesRegistered as $registeredPath => $handle) {

            $segmentsRegistered = $this->getPathSegments( trim($registeredPath, '/') );
            $pathsMatch = (count($segmentsRegistered) == count($segmentsFromRequest));

            if ($pathsMatch && ! empty($segmentsRegistered) ) {

                foreach ($segmentsFromRequest as $key => $segment) {
                    $pathsMatch = ($segmentsFromRequest[$key] == $segmentsRegistered[$key]);
                    $areWilds = preg_match_all("/^\\{(.*)\\}$/U", $segmentsRegistered[$key], $wildMatches);

                    if ( ! empty($areWilds)) {
                        $wildKey = preg_replace("/\\W/", '', $wildMatches[1][0]);
                        $args[$wildKey] = $segmentsFromRequest[$key];
                    }

                    if ( ! $pathsMatch && empty($areWilds)) {
                        break;
                    }

                }

                if ($pathsMatch || ! empty($areWilds)) {
                    $this->runRoute($requestPath, $handle, $args);
                    break;
                }

            }
        }
    }

    /**
     * @param $method
     *
     * @return array
     */
    public function getRegisteredRoutesByMethod($method)
    {
        $method = strtoupper($method);
        $routesRegistered = [];

        if (isset(self::$routes[$method])) {
            $routesRegistered = self::$routes[$method];
        }

        return $routesRegistered;
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    private function getPathSegments($path)
    {
        return explode('/', $path);
    }

}