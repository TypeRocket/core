<?php

namespace TypeRocket\Http;

class Route
{
    public $match;
    public $do;
    public $middleware;
    public $methods;
    public $addTrailingSlash = true;

    public function match($regex, $map = [])
    {
        $this->match = [ltrim($regex, '/'), $map, $this];
        return $this;
    }

    public function middleware(array $middleware)
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function do($handle)
    {
        $this->do = $handle;
        $this->registerRoute();
        return $this;
    }

    /**
     * Do not redirect route with trailing slash
     *
     * @return $this
     */
    public function noTrailingSlash()
    {
        $this->addTrailingSlash = false;
        return $this;
    }

    /**
     * Add Get Route
     */
    public function get()
    {
        $this->methods[] = 'GET';
        return $this;
    }

    /**
     * Add Post Route
     */
    public function post()
    {
        $this->methods[] = 'POST';
        return $this;
    }

    /**
     * Add Put Route
     */
    public function put()
    {
        $this->methods[] = 'PUT';
        return $this;
    }

    /**
     * Add Delete Route
     */
    public function delete()
    {
        $this->methods[] = 'DELETE';
        return $this;
    }

    /**
     * Add Patch Route
     */
    public function patch()
    {
        $this->methods[] = 'PATCH';
        return $this;
    }

    /**
     * Add Options Route
     */
    public function options()
    {
        $this->methods[] = 'OPTIONS';
        return $this;
    }

    /**
     * Add Any Route
     */
    public function any()
    {
        $this->methods = ['PUT', 'POST', 'GET', 'DELETE', 'PATCH', 'OPTIONS'];
        return $this;
    }

    protected function registerRoute() {
        Routes::addRoute($this);
    }

}