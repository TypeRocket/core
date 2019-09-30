<?php


namespace TypeRocket\Http;


class RouteCollection
{
    public static $routes = [];

    /**
     * Add Route
     *
     * @param Route $route
     */
    public static function addRoute( $route )
    {
        self::$routes[] = $route;
    }

    /**
     * Get Registered Routes
     *
     * @param string $method POST, PUT, DELETE, GET
     * @return array
     */
    public static function getRegisteredRoutes($method)
    {
        $method = strtoupper($method);
        $routesRegistered = [];

        /** @var Route $route */
        foreach (self::$routes as $route) {
            if (in_array($method, $route->methods)) {
                $routesRegistered[] = $route->match;
            }
        }

        return $routesRegistered;
    }
}