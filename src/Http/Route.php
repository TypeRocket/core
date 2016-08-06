<?php

namespace TypeRocket\Http;

class Route
{

    /**
     * Add Get Route
     *
     * @param string $path request path rewrite to add
     * @param string $handler the call that handles the request
     */
    public function get($path, $handler)
    {
        Routes::addRoute('GET', $path, $handler);
    }

    /**
     * Add Post Route
     *
     * @param string $path request path rewrite to add
     * @param string $handler the action controller call
     */
    public function post($path, $handler)
    {
        Routes::addRoute('POST', $path, $handler);
    }

    /**
     * Add Put Route
     *
     * @param string $path request path rewrite to add
     * @param string $handler the action controller call
     */
    public function put($path, $handler)
    {
        Routes::addRoute('PUT', $path, $handler);
    }

    /**
     * Add Delete Route
     *
     * @param string $path request path rewrite to add
     * @param string $handler the action controller call
     */
    public function delete($path, $handler)
    {
        Routes::addRoute('DELETE', $path, $handler);
    }

    /**
     * Add Any Route
     *
     * @param string $path request path rewrite to add
     * @param string $handler the action controller call
     */
    public function any($path, $handler)
    {
        Routes::addRoute('PUT', $path, $handler);
        Routes::addRoute('POST', $path, $handler);
        Routes::addRoute('GET', $path, $handler);
        Routes::addRoute('DELETE', $path, $handler);
    }

}