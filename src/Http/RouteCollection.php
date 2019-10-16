<?php


namespace TypeRocket\Http;

/**
 * Class RouteCollection
 *
 * This class is added to the Injector
 *
 * @package TypeRocket\Http
 */
abstract class RouteCollection
{
    public $routes = [];
    protected $named = [];

    /**
     * Add Route
     *
     * @param Route $route
     * @return mixed
     */
    public function addRoute( $route )
    {
        if($route->name) {
            $this->named[$route->name] = $route;
        }

        $this->routes[] = $route;

        return $this;
    }

    /**
     * Count Routes
     *
     * @return int
     */
    public function count()
    {
        return count($this->routes);
    }

    /**
     * Get Registered Routes
     *
     * @param string $method POST, PUT, DELETE, GET
     * @return array
     */
    public function getRegisteredRoutes($method)
    {
        $method = strtoupper($method);
        $routesRegistered = [];

        /** @var Route $route */
        foreach ($this->routes as $route) {
            if (in_array($method, $route->methods)) {
                $routesRegistered[] = $route->match;
            }
        }

        return $routesRegistered;
    }

    /**
     * Get Named Route
     *
     * @param $name
     * @return Route|null
     */
    public function getNamedRoute($name)
    {
        return $this->named[$name] ?? null;
    }
}