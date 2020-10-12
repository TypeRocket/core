<?php
namespace TypeRocket\Http;

use TypeRocket\Models\WPUser;
use TypeRocket\Utility\Str;

class Request
{
    protected $method = null;
    protected $uri = null;
    protected $referer = null;
    protected $path = null;
    protected $host = null;
    protected $fields = null;
    protected $post = null;
    protected $get = null;
    protected $input = null;
    protected $files = null;
    protected $cookies = null;
    protected $protocol = 'http';

    /**
     * Construct the request
     *
     * @internal param int $id the resource ID
     */
    public function __construct()
    {
        $this->method = $this->getFormMethod();
        $this->protocol = is_ssl() ? 'https' : 'http';
        $this->post = !empty($_POST) ? wp_unslash($_POST) : null;
        $this->get = !empty($_GET) ? wp_unslash($_GET) : null;
        $this->files = $_FILES ?? null;
        $this->uri = $_SERVER['REQUEST_URI'] ?? null;
        $this->referer = $_SERVER['HTTP_REFERER'] ?? null;
        $this->host = $_SERVER['HTTP_HOST'] ?? null;

        if( ! empty( $_SERVER['REQUEST_URI'] ) ) {
            $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
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
     * Is Put
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->method == 'DELETE';
    }

    /**
     * Is Marked AJAX
     *
     * @return bool
     */
    public function isMarkedAjax()
    {
        return !empty($this->post['_tr_ajax_request']) || !empty($this->get['_tr_ajax_request']);
    }

    /**
     * Is Maybe Ajax
     *
     * The JavaScript sending the request needs to have applied
     * the custom header HTTP_X_REQUESTED_WITH.
     *
     * Maybe add: wp_doing_ajax()
     *
     * @return bool
     */
    public function isAjax()
    {
        $ajax = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        if(strtolower($ajax) == 'xmlhttprequest' || $this->isMarkedAjax() ) {
            return true;
        }

        return false;
    }

    /**
     * Get Agents
     *
     * @return array
     */
    public function getAccepts()
    {
        return explode(',', $_SERVER['HTTP_ACCEPT'] ?? '');
    }

    /**
     * Accept Contains
     *
     * @param $search
     * @return bool
     */
    public function acceptContains($search)
    {
        return Str::contains($search, $_SERVER['HTTP_ACCEPT'] ?? '');
    }

    /**
     * Request Wants
     *
     * @param string $name
     * @return bool|null
     */
    public function wants($name) {
        $types = [
            'json' => 'application/json',
            'html' => 'text/html',
            'xml' => 'application/xml',
            'plain' => 'text/pain',
            'any' => '*/*',
            'image' => 'image/',
        ];

        $search = $types[$name] ?? $name;
        return $search ? $this->acceptContains($search) : false;
    }

    /**
     * Get the form method
     *
     * @return string POST|DELETE|PUT|GET
     */
    public function getFormMethod()
    {
        return $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get Form Prefix
     *
     * @param string $default
     *
     * @return mixed|string
     */
    public function getFormPrefix($default = 'tr')
    {
        return $_POST['_tr_form_prefix'] ?? $default;
    }

    /**
     * Get Full URL
     *
     * @return string
     */
    public function getUriFull()
    {
        return $this->protocol.'://'.$this->host.$this->uri;
    }

    /**
     * Get Path Without Root
     *
     * @param null|string $root
     * @return string
     */
    public function getPathWithoutRoot($root = null)
    {
        $root = $root ?? get_site_url();
        $site =  trim( parse_url($root, PHP_URL_PATH), '/');
        return ltrim( Str::trimStart(ltrim($this->path, '/'), $site), '/');
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
     * Get Http Header
     *
     * @param string $header
     *
     * @return mixed|null
     */
    public function getHeader($header)
    {
        $header = preg_replace( '/[^A-Z0-9_]/', '', strtoupper($header));

        return $_SERVER['HTTP_' . $header] ?? null;
    }

    /**
     * Get the request referer
     *
     * @param bool $fallback
     * @return null|string
     */
    public function getReferer($fallback = true)
    {
        $fallback = $fallback ? $this->getUriFull() : null;

        return $this->referer ?? $fallback;
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
     * Get Input
     *
     * @param null|string $key
     * @param null|string|array $default
     *
     * @return string|null
     */
    public function input($key = null, $default = null)
    {
        return $this->getInput($key, $default);
    }

    /**
     * Get Input
     *
     * @param null|string $key
     * @param null|string|array $default
     *
     * @return string|null
     */
    public function getInput($key = null, $default = null)
    {
        return $this->getDataJson($key, $default) ?? $this->get[$key] ?? $default;
    }

    /**
     * Get Data JSON first or POST
     *
     * @param null|string $key
     * @param null|string|array $default
     *
     * @return mixed|null
     */
    public function getDataJson($key = null, $default = null)
    {
        if(!$this->input) {
            $input = file_get_contents('php://input');
            if(tr_is_json($input)) { $data = json_decode($input, true); }
            else { $data = $this->post; /* parse_str($input, $data); */ }
            $this->input = $data;
        }

        return is_null($key) ? $this->input : ($this->input[$key] ?? $default);
    }

    /**
     * Get the $_POST data
     *
     * @param null|string $key
     * @param null|string|array $default
     *
     * @return null
     */
    public function getDataPost( $key = null, $default = null )
    {
        return is_null($key) ? $this->post : ($this->post[$key] ?? $default);
    }

    /**
     * Get the $_GET data
     *
     * @param null|string $key
     * @param null|string|array $default
     *
     * @return null
     */
    public function getDataGet( $key = null, $default = null )
    {
        return is_null($key) ? $this->get : ($this->get[$key] ?? $default);
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
        parse_str(parse_url($this->getUriFull(), PHP_URL_QUERY), $request_params);

        return $request_params;
    }

    /**
     * Get Full URL with Merged Query
     *
     * @param array $request_params
     * @return string
     */
    public function getModifiedUri(array $request_params = [])
    {
        $parts = parse_url($this->getUriFull());
        parse_str($parts['query'] ?? '', $query);
        $query = http_build_query(array_merge($query, $request_params));

        $map = [
            $parts['scheme'],
            '://',
            $parts['host'],
            !empty($parts['port']) ? ':'.$parts['port'] : null,
            $parts['path'],
            $query ? '?' : '',
            $query,
        ];

        return implode('', $map);
    }

    /**
     * Get the $_COOKIE data
     *
     * @param null|string $key
     * @param null|string|array $default
     *
     * @return null
     */
    public function getDataCookies($key = null, $default = null)
    {
        if(!$this->cookies) {
            $this->cookies = !empty($_COOKIE) ? wp_unslash($_COOKIE) : null;
        }

        return is_null($key) ? $this->cookies : ($this->cookies[$key] ?? $default);
    }

    /**
     * Get the fields
     *
     * @param null|string $key
     * @param null|string|array $default
     * @param string $prefix
     *
     * @return array|null
     */
    public function fields($key = null, $default = null, $prefix = 'tr')
    {
        return $this->getFields($key, $default, $prefix);
    }

    /**
     * Get the fields
     *
     * @param null|string $key
     * @param null|string|array $default
     * @param string $prefix
     *
     * @return array|null
     */
    public function getFields($key = null, $default = null, $prefix = 'tr')
    {
        $fields = $this->getDataJson($prefix);
        return is_null($key) ? $fields : ($fields[$key] ?? $default);
    }

    /**
     * Get Current User
     *
     * @return WPUser|null
     */
    public function getCurrentUser()
    {
        return tr_container('user');
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }

}
