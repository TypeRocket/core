<?php

namespace TypeRocket\Http;

use TypeRocket\Core\Injector;

class Route
{
    public $match;
    public $do;
    public $middleware;
    public $methods;
    public $addTrailingSlash = true;

    /**
     * Match URL Path
     *
     * @param string $regex regular expression to match URL path
     * @param array $map an array of values to mark regex capture groups
     * @param bool $clean trim beginning and ending forward slashes
     * @return $this
     */
    public function match($regex, $map = [], $clean = true)
    {
        $this->match = [$clean ? trim($regex, '/') : $regex, $map, $this];
        return $this;
    }

    /**
     * Add Middleware Classes
     *
     * This method does not accept middleware groups.
     *
     * @param array|string $middleware list of middleware classes to use for the route or string name of group
     * @return $this
     */
    public function middleware($middleware)
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Handler
     *
     * This takes a callable or a quick route decoration.
     *
     * @link https://typerocket.com/docs/v4/routes/#section-quick-route-declarations
     *
     * @param mixed $handle
     * @return $this
     */
    public function do($handle)
    {
        $this->do = $handle;
        return $this;
    }

    /**
     * Do not redirect route with trailing slash
     *
     * @param bool $value
     *
     * @return $this
     */
    public function noTrailingSlash($value = true)
    {
        $this->addTrailingSlash = !$value;
        return $this;
    }

    /**
     * Add Get Route
     *
     * @return $this
     */
    public function get()
    {
        $this->methods[] = 'GET';
        return $this;
    }

    /**
     * Add Post Route
     *
     * @return $this
     */
    public function post()
    {
        $this->methods[] = 'POST';
        return $this;
    }

    /**
     * Add Put Route
     *
     * @return $this
     */
    public function put()
    {
        $this->methods[] = 'PUT';
        return $this;
    }

    /**
     * Add Delete Route
     *
     * @return $this
     */
    public function delete()
    {
        $this->methods[] = 'DELETE';
        return $this;
    }

    /**
     * Add Patch Route
     *
     * @return $this
     */
    public function patch()
    {
        $this->methods[] = 'PATCH';
        return $this;
    }

    /**
     * Add Options Route
     *
     * @return $this
     */
    public function options()
    {
        $this->methods[] = 'OPTIONS';
        return $this;
    }

    /**
     * Add Any Route
     *
     * @return $this
     */
    public function any()
    {
        $this->methods = ['PUT', 'POST', 'GET', 'DELETE', 'PATCH', 'OPTIONS'];
        return $this;
    }

    /**
     * Register the route
     *
     * @param null|RouteCollection $routes
     * @return $this
     */
    public function register($routes = null) {
        /** @var RouteCollection $routes */
        $routes = $routes instanceof RouteCollection ? $routes : Injector::resolve(RouteCollection::class);
        $routes->addRoute($this);

        return $this;
    }

}