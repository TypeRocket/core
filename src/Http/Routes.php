<?php

namespace TypeRocket\Http;

use TypeRocket\Http\Responders\ResourceResponder,
    TypeRocket\View;

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
    public static $vars = [];
    public static $request;

    public function __construct()
    {
        self::$request = new Request();

        add_filter('do_parse_request', \Closure::bind(function( $bool ) {
            $this->route();
            return $bool;
        }, $this) );
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
     *  Load the template for the front-end without globals
     */
    private function getTemplate() {
        extract(View::$data);
        include ( View::$template );
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
        $args = [$path, self::$request, $wilds];
        self::$vars = $wilds;

        if( ! str_ends('/', self::$request->getPath() ) ) {
            wp_redirect( self::$request->getPath() . '/' );
            die();
        }

        if (is_callable($handle)) {
            call_user_func_array($handle, $args);
        } else {
            list($action, $resource) = explode('@', $handle);
            $respond = new ResourceResponder();
            $respond->setResource( ucfirst($resource) );
            $respond->setAction( $action );
            $respond->setActionMethod( strtoupper( self::$request->getFormMethod() ) );
            $respond->respond( isset($wilds['id']) ? $wilds['id'] : null );
            $this->getTemplate();
        }
        die();
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