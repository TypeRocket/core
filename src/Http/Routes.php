<?php

namespace TypeRocket\Http;

use Closure;
use TypeRocket\Database\Results;
use TypeRocket\Http\Responders\ResourceResponder;
use TypeRocket\Models\Model;
use TypeRocket\Utility\Str;
use WP_Query;

/**
 * Class Routes
 *
 * Store all the routes for TypeRocket Resources if there are any.
 *
 * @package TypeRocket\Http
 */
class Routes
{
    /** @var array $vars */
    public $vars = [];
    public $config = [];
    public $request;
    public $routes;
    public $match = [];

    /**
     * Routes constructor.
     * @param Request $request
     * @param null|array $config
     * @param RouteCollection $routes
     */
    public function __construct($request, $config, RouteCollection $routes)
    {
        $this->request = $request;
        $this->config = $config;
        $this->routes = $routes;
    }

    /**
     * Init Hooks
     *
     * @return $this
     */
    public function initHooks()
    {
        if( ! is_admin() ) {
            // template_include is needed to keep admin bar
            add_filter('template_include', Closure::bind(function( $template ) {
                $this->route();
                return $template;
            }, $this) );

            add_filter( 'posts_request', function($sql, $q) {
                /** @var WP_Query $q */
                if ( $q->is_main_query() && !empty($q->query['tr_route_var']) ) {
                    // disable row count
                    $q->query_vars['no_found_rows'] = true;

                    // disable cache
                    $q->query_vars['cache_results'] = false;
                    $q->query_vars['update_post_meta_cache'] = false;
                    $q->query_vars['update_post_term_cache'] = false;

                    add_filter('body_class', function($classes) { array_push($classes, 'custom-route'); return $classes; });
                    return false;
                }
                return $sql;
            }, 10, 3 );
        }

        add_action('option_rewrite_rules', Closure::bind(function($value) {
            return $this->spoofRewrite($value);
        }, $this));

        return $this;
    }

    /**
     * Spoof Rewrite Rules
     *
     * @param string|array $value
     *
     * @return array
     */
    public function spoofRewrite( $value )
    {
        $match = $this->match;
        $add = [];
        if( !empty($match)) {
            $key = '^' . rtrim($this->match[0], '/') . '/?$';
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
        $args = [$path, $this->request, $wilds];
        $this->vars = $wilds;
        $addSlash = $this->match[1]->addTrailingSlash ?? true;
        $path = $this->request->getPath();
        $endsInSlash = Str::ends('/', $path );

        if( $addSlash && ! $endsInSlash && $this->request->isGet() ) {
            wp_redirect( $path . '/' );
            die();
        } elseif( ! $addSlash && $endsInSlash && $this->request->isGet() ) {
            wp_redirect( rtrim($path, '/') );
            die();
        }

        if (is_callable($handle->do)) {
            $args[2]['response'] = tr_response();
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
        if( $returned instanceof Model ) {
            wp_send_json( $returned->toArray() );
        }

        if( $returned instanceof Results ) {
            wp_send_json($returned->toArray());
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
     * @param null|string $root
     * @return Routes
     */
    public function detectRoute()
    {
        $request = $this->request;
        $root = $this->config['root'] ?? null;

        $path = $request->getPath();
        $routesRegistered = $this->routes->getRegisteredRoutes($this->request->getFormMethod());

        $requestPath = $toMatchUrl = ltrim($path, '/');

        /**
         * Match routes with the site sub folder removed from the URL
         */
        if( $root || (!empty($this->config['match']) && $this->config['match'] == 'site_url') ) {
            $site_path =  trim(parse_url($root ?? get_site_url(), PHP_URL_PATH), '/');
            $toMatchUrl = ltrim( Str::trimStart($requestPath, $site_path), '/');
        }

        $toMatchUrl = apply_filters('tr_routes_path', $toMatchUrl );

        list($match, $args) = $this->matchRoute($toMatchUrl, $routesRegistered);

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
     * @param $redirect_url
     * @param $requested_url
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

}
