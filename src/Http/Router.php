<?php
namespace TypeRocket\Http;

use TypeRocket\Http\Responders\HttpResponder;
use TypeRocket\Utility\Str;
use WP_Query;

/**
 * Class Routes
 *
 * Store all the routes for TypeRocket Resources if there are any.
 *
 * @package TypeRocket\Http
 */
class Router
{
    /** @var array $args */
    public $args = [];

    /** @var Route|null  */
    public $route = null;
    public $path = null;
    public $config = [];
    /** @var Request  */
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
            add_filter('template_include', function( $template ) {
                if( $this->route ) {
                    $this->runRoute();
                }
                return $template;
            });

            add_filter( 'query_vars', function($vars) {
                $vars[] = 'tr_route_var';
                return $vars;
            } );

            add_filter( 'posts_request', function($sql, $q) {
                /** @var WP_Query $q */
                if ( $q->is_main_query() && !empty($q->query['tr_route_var']) ) {
                    // disable row count
                    $q->query_vars['no_found_rows'] = true;

                    // disable cache
                    $q->query_vars['cache_results'] = false;
                    $q->query_vars['update_post_meta_cache'] = false;
                    $q->query_vars['update_post_term_cache'] = false;

                    // disable sticky posts
                    $q->query_vars['ignore_sticky_posts'] = true;

                    add_filter('body_class', function($classes) { array_push($classes, 'custom-route'); return $classes; });
                    return false;
                }
                return $sql;
            }, 10, 3 );

            add_action('option_rewrite_rules', function($value) {
                return $this->spoofRewrite($value);
            });
        }

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
        $add = [];
        if( $this->route ) {
            $key = '^' . rtrim($this->route->match['regex'], '/') . '/?$';
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
     */
    private function runRoute()
    {
        $wpTrailingslash = static::wpWantsTrailingSlash();
        $requestedPath = $this->request->getPath();
        $forceRemoveSlash = $this->route->addTrailingSlash === false;
        $requestEndsInSlash = Str::ends('/', $requestedPath);
        $isGet = $this->request->isGet();
        $redirect = null;

        if( !$forceRemoveSlash && !$requestEndsInSlash && $wpTrailingslash && $isGet ) {
            $redirect = $requestedPath . '/';
        } elseif( ($forceRemoveSlash || !$wpTrailingslash) && $requestEndsInSlash && $isGet ) {
            $redirect = rtrim($requestedPath, '/');
        }

        if($redirect) {
            $redirect = Str::replaceFirst($requestedPath, $redirect, $this->request->getUri());
            $redirect = apply_filters('typerocket_route_redirect', $redirect, $this, $wpTrailingslash);

            if($redirect) {
                wp_redirect($redirect, 301);
                die();
            }
        }

        $responder = new HttpResponder;

        $responder
            ->getHandler()
            ->setRoute( $this->route )
            ->setController( $this->route->do );

        $responder->respond( $this->args );
        \TypeRocket\Http\Response::getFromContainer()->finish();
    }

    /**
     * Route request through registered routes if these is a match
     * @return Router
     */
    public function detectRoute()
    {
        $root = $this->config['root'] ?? get_site_url();

        $routesRegistered = $this->routes->getRegisteredRoutes($this->request->getFormMethod());
        $this->path = $this->request->getPathWithoutRoot($root);

        if( $this->matchRoute($this->path, $routesRegistered) ) {
            add_filter( 'redirect_canonical', [$this, 'redirectCanonical'] , 10, 2);
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
    public function redirectCanonical($redirect_url, $requested_url)
    {
        remove_filter('redirect_canonical', [$this, 'redirectCanonical']);
        return $requested_url;
    }

    /**
     * @param string $uri path to match
     * @param array $routes list of routes
     *
     * @return bool
     */
    public function matchRoute($uri, $routes) {

        if(empty($routes)) { return false; }

        $regex = ['#^(?'];
        foreach ($routes as $i => $route) {
            $slash = $route['route']->addTrailingSlash || $route['route']->addTrailingSlash === null ? '\/?$' : '';
            $regex[] = rtrim($route['regex'], '/') . $slash . '(*MARK:'.$i.')';
        }
        $regex = implode('|', $regex) . ')$#x';
        preg_match($regex, $uri, $m);

        if(empty($m)) { return false; }

        $r = $routes[$m['MARK']];
        $args = [];

        if(empty($r)) { return false; }

        if(!empty($r['args'])) {
            foreach ($r['args'] as $i => $arg) {
                if($v = $m[$i + 1] ?? null) {
                    $args[$arg] = $v;
                }
            }
        } else {
            foreach ($m as $i => $arg) {
                if($i > 0 && $i !== 'MARK') {
                    $args[] = $arg;
                }
            }
        }


        $this->route = $r['route'];
        $this->args = $args;

        return true;
    }

    /**
     * @return bool
     */
    public static function wpWantsTrailingSlash() {
        global $wp_rewrite;
        return Str::ends('/', $wp_rewrite->permalink_structure ?? '/');
    }

}
