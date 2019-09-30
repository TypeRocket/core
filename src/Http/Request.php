<?php
namespace TypeRocket\Http;

class Request
{
    
    protected $method = null;
    protected $uri = null;
    protected $path = null;
    protected $host = null;
    protected $fields = null;
    protected $post = null;
    protected $get = null;
    protected $files = null;
    protected $cookies = null;
    protected $hook = false;
    protected $protocol = 'http';
    protected $rest = false;
    protected $custom;

    /**
     * Construct the request
     *
     * @param string $method the method PUT, POST, GET, DELETE
     * @param bool $hook
     * @param bool $rest
     * @param bool $custom
     * @internal param int $id the resource ID
     */
    public function __construct( $method = null, $hook = false, $rest = false, $custom = false )
    {
        $this->method = is_string($method) ? $method : $this->getFormMethod();
        $this->protocol = get_http_protocol();
        $this->post = !empty ($_POST) ? wp_unslash($_POST) : null;
        $this->fields = !empty ($this->post['tr']) ? $this->post['tr'] : [];
        $this->get = !empty ($_GET) ? wp_unslash($_GET) : null;
        $this->files = !empty ($_FILES) ? $_FILES : null;
        $this->cookies = !empty ($_COOKIE) ? wp_unslash($_COOKIE) : null;
        $this->uri = !empty ($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
        $this->host = !empty ($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;

        if( ! empty( $_SERVER['REQUEST_URI'] ) ) {
            $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
        $this->hook = $hook;
        $this->rest = $rest;
        $this->custom = $custom;
    }

    /**
     * Get the HTTP protocol
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
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
     * Is Get
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->method == 'GET';
    }

    /**
     * Is Post
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->method == 'POST';
    }

    /**
     * Is Put
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->method == 'PUT';
    }

    /**
     * Is Delete
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->method == 'DELETE';
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
        if( $key && array_key_exists($key, $this->post ?? [])) {
            return $this->post[$key];
        }

        return !$key ? $this->post : null;
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
        if( isset($key) && array_key_exists($key, $this->get ?? [])) {
            return $this->get[$key];
        }

        return !$key ? $this->get : null;
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
     * Get URI Query as Array
     *
     * @return mixed
     */
    public function getQueryAsArray()
    {
        parse_str(parse_url($this->uri, PHP_URL_QUERY), $request_params);

        return $request_params;
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
     * @return bool
     */
    public function isHook()
    {
        return $this->hook;
    }

    /**
     * @return bool
     */
    public function isRest()
    {
        return $this->rest;
    }

    /**
     * @return bool
     */
    public function isCustom()
    {
        return $this->custom;
    }

}
