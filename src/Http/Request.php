<?php
namespace TypeRocket\Http;

class Request
{

    private $resource = null;
    private $action = null;
    private $hook = null;
    private $method = null;
    private $routerArgs = [];
    private $uri = null;
    private $path = null;
    private $host = null;
    private $fields = null;
    private $post = null;
    private $get = null;
    private $files = null;
    private $cookies = null;

    /**
     * Construct the request
     *
     * @param string $resource the resource
     * @param string $method the method PUT, POST, GET, DELETE
     * @param array $args the router args
     * @param string $action
     * @param bool $hook
     *
     * @internal param int $id the resource ID
     */
    public function __construct( $resource = null, $method = null, $args = null, $action = 'auto', $hook = false )
    {
        $this->resource = $resource;
        $this->routerArgs = $args;
        $this->action = $action;
        $this->hook = $hook;
        $this->method = $method ? $method : $this->getFormMethod();
        $this->post   = ! empty ( $_POST ) ? wp_unslash($_POST) : null;
        $this->fields = ! empty ( $this->post['tr'] ) ? $this->post['tr'] : [];
        $this->get    = ! empty ( $_GET ) ? wp_unslash($_GET) : null;
        $this->files  = ! empty ( $_FILES ) ? $_FILES : null;
        $this->cookies  = ! empty ( $_COOKIE ) ? wp_unslash($_COOKIE) : null;
        $this->uri    = ! empty ( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;
        $this->host   = ! empty ( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null;

        if( ! empty( $_SERVER['REQUEST_URI'] ) ) {
            $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
    }

    /**
     * Set the method
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the method
     *
     * @return null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the form method
     *
     * @return string POST|DELETE|PUT|GET
     */
    public function getFormMethod()
    {
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        return ( isset( $_POST['_method'] ) ) ? $_POST['_method'] : $method;
    }

    /**
     * Get the resource
     *
     * @return null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get the router args
     *
     * @return null
     */
    public function getRouterArgs()
    {
        return $this->routerArgs;
    }

    /**
     * Get the router arg
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function getRouterArg($key, $default = null)
    {
        if( array_key_exists($key, $this->routerArgs) ) {
            $default = $this->routerArgs[$key];
        }

        return $default;
    }

    /**
     * Get the request URI
     *
     * @return null|string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Get the request path
     *
     * @return mixed|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the request path exploded into an array
     *
     * @return mixed|null
     */
    public function getPathExploded()
    {
        return explode('/', trim($this->path, '/') );
    }

    /**
     * Get the host
     *
     * @return null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the $_POST data
     *
     * @param null $key
     *
     * @return null
     */
    public function getDataPost( $key = null )
    {
        if( array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        return $this->post;
    }

    /**
     * Get the $_GET data
     *
     * @param null $key
     *
     * @return null
     */
    public function getDataGet( $key = null )
    {
        if( array_key_exists($key, $this->get)) {
            return $this->get[$key];
        }

        return $this->get;
    }

    /**
     * Get the $_FILES data
     *
     * @return null
     */
    public function getDataFiles()
    {
        return $this->files;
    }

    /**
     * Get the $_COOKIE data
     *
     * @param null $key
     *
     * @return null
     */
    public function getDataCookies( $key = null )
    {
        if( array_key_exists($key, $this->cookies)) {
            return $this->cookies[$key];
        }

        return $this->cookies;
    }

    /**
     * Get the fields
     *
     * @param null $key
     *
     * @return array|null
     */
    public function getFields($key = null)
    {
        if( array_key_exists($key, $this->fields)) {
            return $this->fields[$key];
        }

        return $this->fields;
    }

    /**
     * Check if request is a hook action
     *
     * @return bool|null
     */
    public function isHook()
    {
        return $this->hook;
    }

}
