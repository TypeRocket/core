<?php

namespace TypeRocket\Http;

use TypeRocket\Database\Results;
use TypeRocket\Http\Responders\ResourceResponder;
use TypeRocket\Models\Model;
use TypeRocket\Template\View;
use TypeRocket\Utility\Str;

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
    public $vars = [];
    public static $request;
    public $match = [];

    /**
     * Routes constructor.
     */
    public function __construct()
    {
        self::$request = new Request();
    }

    /**
     * Init Hooks
     *
     * @return $this
     */
    public function initHooks()
    {
        if( ! is_admin() ) {
            add_filter('template_include', \Closure::bind(function( $template ) {
                $this->route();
                return $template;
            }, $this) );

            add_filter( 'posts_request', function($sql, $q) {
                if ( $q->is_main_query() && !empty($q->query['tr_route_var']) ) {
                    // disable row count
                    $q->query_vars['no_found_rows'] = true;

                    // disable cache
                    $q->query_vars['cache_results'] = false;
                    $q->query_vars['update_post_meta_cache'] = false;
                    $q->query_vars['update_post_term_cache'] = false;
                    return false;
                }
                return $sql;
            }, 10, 3 );
        }

        add_action('option_rewrite_rules', \Closure::bind(function($value) {
            return $this->spoofRewrite($value);
        }, $this));

        return $this;
    }

    /**
     * Spoof Rewrite Rules
     *
     * @param string $value
     *
     * @return array
     */
    public function spoofRewrite( $value)
    {
        $match = $this->match;
        $add = [];
        if( !empty($match)) {
            $key = '^' . $this->match[0] . '/?$';
            if( !empty($value[$key]) ) {
                unset($value[$key]);
            }
            $add[$key] = 'index.php?tr_route_var=1';

            if(is_array($value)) {
                $value = array_merge($add, $value);
            } else {
                $value = $add;
            }
        }
        return $value;
    }

    /**
     * Add Route
     *
     * @param \TypeRocket\Http\Route $route
     */
    public static function addRoute( $route )
    {
        self::$routes[] = $route;
    }

    /**
     * Run route if there is a callback
     *
     * If the callback is not a controller pass in the Response
     * object as the argument $response
     *
     * @param string|null $path
     * @param object|null $handle
     * @param array|null $wilds
     */
    private function runRoute($path = null, $handle = null, $wilds = null)
    {
        $args = [$path, self::$request, $wilds];
        $this->vars = $wilds;
        $addSlash = $this->match[1]->addTrailingSlash ?? true;
        $path = self::$request->getPath();
        $endsInSlash = Str::ends('/', $path );

        if( $addSlash && ! $endsInSlash && self::$request->isGet() ) {
            wp_redirect( $path . '/' );
            die();
        } elseif( ! $addSlash && $endsInSlash && self::$request->isGet() ) {
            wp_redirect( rtrim($path, '/') );
            die();
        }

        if (is_callable($handle->do)) {
            $response = new Response();
            $args[2]['response'] = $response;
            $map = resolve_method_args($handle->do, $args[2]);
            tr_http_response(resolve_method_map($map), $args[2]['response']);
        } else {
            list($action, $resource) = explode('@', $handle->do);
            list($resource, $handler) = array_pad(explode(':', $resource, 2), 2, null);
            $respond = new ResourceResponder();
            $respond->setResource( ucfirst($resource) );
            $respond->setAction( $action );
            $respond->setRoute( $handle );
            $respond->setHandler( $handler );
            $respond->respond( $this->vars );
        }

        die();
    }

    /**
     * Results To JSON
     *
     * Return a model or result object as json.
     *
     * @param string $returned
     */
    public static function resultsToJson($returned)
    {
        $result = [];

        if( $returned instanceof Model ) {
            wp_send_json( $returned->getProperties() );
        }

        if( $returned instanceof Results ) {
            foreach ($returned as $record) {
                $result[] = $record->getProperties();
            }
            wp_send_json($result);
        }

        if( is_array($returned) ) {
            wp_send_json($returned);
        }
    }

    /**
     * Route
     */
    public function route()
    {
        if( !empty($this->match)) {
            list($path, $handle, $wilds) = $this->match;
            $this->runRoute($path, $handle, $wilds);
        }
    }

    /**
     * Route request through registered routes if these is a match
     */
    public function detectRoute()
    {
        $path = self::$request->getPath();
        $requestPath = ltrim($path, '/');
        $requestPath = apply_filters('tr_routes_path', $requestPath );
        $routesRegistered = $this->getRegisteredRoutes();

        list($match, $args) = $this->matchRoute(rtrim($requestPath, '/'), $routesRegistered);

        if($match) {
            $this->match = [$requestPath, $match[2], $args];
            add_filter( 'redirect_canonical', [$this, 'redirect_canonical'] , 10, 2);
        }

        return $this;
    }

    /**
     * Custom Routes Will Add Trailing Slash
     *
     * Custom routes always add a trailing slash unless otherwise
     * defined in route declaration. We do not need WP to handle
     * this functionality for us.
     *
     * @return $this
     */
    public function redirect_canonical($redirect_url, $requested_url)
    {
        remove_filter('redirect_canonical', [$this, 'redirect_canonical']);
        return $requested_url;
    }

    /**
     * @param string $uri path to match
     * @param array $routes list of routes
     *
     * @return array
     */
    public function matchRoute($uri, $routes) {

        if(empty($routes)) { return [null, null];}

        $regex = ['#^(?'];
        foreach ($routes as $i => $route) {
            $slash = $route[2]->addTrailingSlash ? '\/?$' : '';
            $regex[] = rtrim($route[0], '/') . $slash . '(*MARK:'.$i.')';
        }
        $regex = implode('|', $regex) . ')$#x';
        preg_match($regex, $uri, $m);

        if(empty($m)) { return [null, null];}

        $r = $routes[$m['MARK']];
        $args = [];

        if(empty($r)) { return [null, null];}

        foreach ($r[1] as $i => $arg) {
            $args[$arg] = $m[$i + 1];
        }
        return [$r, $args];
    }

    /**
     * Get Registered Routes
     *
     * @return array
     */
    public function getRegisteredRoutes()
    {
        $method = strtoupper(self::$request->getFormMethod());
        $routesRegistered = [];

        /** @var \TypeRocket\Http\Route $route */
        foreach (self::$routes as $route) {
            if (in_array($method, $route->methods)) {
                $routesRegistered[] = $route->match;
            }
        }

        return $routesRegistered;
    }

}
