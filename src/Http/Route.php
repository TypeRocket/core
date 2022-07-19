<?php
namespace TypeRocket\Http;

use TypeRocket\Core\Container;

class Route
{
    public $match;
    public $name;
    public $pattern;
    public $registeredNamedRoute = false;
    public $do;
    public $middleware;
    public $methods;
    public $addTrailingSlash = null;

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        $route = new static(...$args);
        return $route->register();
    }

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
        $this->match = [ 'regex' => $clean ? trim($regex, '/') : $regex, 'args' => $map, 'route' => $this];
        return $this;
    }

    /**
     * Add Middleware Classes
     *
     * This method does not accept middleware groups.
     *
     * @param array|string $middleware array of middleware classes to use for the route or string name of group
     * @return $this
     */
    public function middleware($middleware)
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Controller
     *
     * @param mixed $controller
     * @return $this
     */
    public function do($controller)
    {
        $this->do = $controller;
        return $this;
    }

    /**
     * Quick Match & Controller
     *
     * @param string $match
     * @param mixed $controller
     *
     * @return $this
     */
    public function on($match, $controller)
    {
        $this->match(str_replace('*', '([^\/]+)',$match));
        $this->do($controller);

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
     * Name Route
     *
     * @param string $name my.custom.route.name
     * @param string $pattern url-path/:id/create
     * @param null|RouteCollection $routes
     * @return $this
     */
    public function name($name, $pattern = null, $routes = null)
    {
        if(!$this->registeredNamedRoute) {
            $this->name = $name;
            $this->pattern = $pattern;

            /** @var RouteCollection $routes */
            $routes = $routes instanceof RouteCollection ? $routes : Container::resolve(RouteCollection::class);
            $routes->registerNamedRoute($this);
        }

        return $this;
    }

    /**
     * Build Url From Pattern
     *
     * @param array $values
     * @param bool $site
     * @return mixed
     */
    public function buildUrlFromPattern(array $values = [], $site = true)
    {
        $pattern = $this->pattern;

        if(!$pattern) {
            $keys = array_map(function($value) {
                return strtolower($value[0] == ':' ? $value : ':' . $value);
            }, $this->match['args']);

            $match = array_map(function($v) { return '/\(.+\)/U'; }, $keys);
            $pattern = preg_replace($match, $keys, $this->match['regex'] ?? null, 1);
        }

        $keys = array_keys($values);

        $keys = array_map(function($value) {
            return strtolower($value[0] == ':' ? $value : ':' . $value);
        }, $keys);

        $built = str_replace($keys, $values, $pattern);
        $url = $site ? site_url( ltrim($built, '/') ) : $built;

        if($this->addTrailingSlash === true || ( $this->addTrailingSlash === null && Router::wpWantsTrailingSlash() )) {
            $url = trailingslashit($url);
        }

        return $url;
    }

    /**
     * Register the route
     *
     * @param null|RouteCollection $routes
     * @return $this
     */
    public function register($routes = null) {
        $routes = $routes instanceof RouteCollection ? $routes : RouteCollection::getFromContainer();
        $routes->addRoute($this);

        return $this;
    }

    /**
     * Get Routes Repo
     *
     * @param string $name
     * @param array $values
     * @param bool $site
     * @param null|RouteCollection $routes
     *
     * @return null|string
     */
    public static function buildUrl($name, $values = [], $site = true, $routes = null)
    {
        $routes = $routes instanceof RouteCollection ? $routes : \TypeRocket\Http\RouteCollection::getFromContainer();
        return $routes->getNamedRoute($name)->buildUrlFromPattern($values, $site);
    }

}