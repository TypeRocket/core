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
     * @param $value
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
     * @param $method
     * @param $path
     * @param $handler
     */
    public static function addRoute( $method, $path, $handler )
    {
        self::$routes[$method][$path] = $handler;
    }

    /**
     * Run route if there is a callback
     *
     * @param null $path
     * @param null $handle
     * @param null $wilds
     */
    private function runRoute($path = null, $handle = null, $wilds = null)
    {
        $args = [$path, self::$request, $wilds];
        $this->vars = $wilds;

        if( ! Str::ends('/', self::$request->getPath() ) && self::$request->getMethod() == 'GET' ) {
            wp_redirect( self::$request->getPath() . '/' );
            die();
        }

        if (is_callable($handle)) {
            $returned = call_user_func_array($handle, $args);

            if( $returned instanceof View ) {
                $this->loadView();
            }

            if( $returned instanceof Redirect ) {
                $returned->now();
            }

            if( is_string($returned) ) {
                echo $returned;
                die();
            }

            self::resultsToJson( $returned );

        } else {
            list($action, $resource) = explode('@', $handle);
            $respond = new ResourceResponder();
            $respond->setResource( ucfirst($resource) );
            $respond->setAction( $action );
            $respond->setActionMethod( strtoupper( self::$request->getFormMethod() ) );
            $respond->respond( $this->vars );
            $this->loadView();
        }

        die();
    }



    /**
     * Results To JSON
     *
     * Return a model or result object as json.
     *
     * @param $returned
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
     *  Load the template for the front-end without globals
     */
    private function loadView() {
        add_filter('document_title_parts', function( $title ) {
            if( is_string(View::$title) ) {
                $title = [];
                $title['title'] = View::$title;
            } elseif ( is_array(View::$title) ) {
                $title = View::$title;
            }
            return $title;
        });

        extract( View::$data );
        /** @noinspection PhpIncludeInspection */
        include ( View::$view );
    }

    /**
     * Route
     */
    public function route()
    {
        if( !empty($this->match)) {
            list($path, $handle, $wilds) =$this->match;
            $this->runRoute($path, $handle, $wilds);
        }
    }

    /**
     * Route request through registered routes if these is a match
     */
    public function detectRoute()
    {
        $requestPath = trim(self::$request->getPath(), '/');
        $routesRegistered = $this->getRegisteredRoutesByMethod( self::$request->getFormMethod() );
        $segmentsFromRequest = $this->getPathSegments( $requestPath );
        $args = null;
        $handle = null;

        foreach ($routesRegistered as $registeredPath => $handle) {

            $segmentsRegistered = $this->getPathSegments( trim($registeredPath, '/') );
            $pathsMatch = (count($segmentsRegistered) == count($segmentsFromRequest));

            if ($pathsMatch && ! empty($segmentsRegistered) ) {

                foreach ($segmentsFromRequest as $key => $segment) {
                    $pathsMatch = ($segmentsFromRequest[$key] == $segmentsRegistered[$key]);
                    $areWilds = preg_match_all("/^\\{(.*)\\}$/U", $segmentsRegistered[$key], $wildMatches);

                    if ( ! empty($areWilds)) {
                        $wildKey = preg_replace("/\\W/", '', $wildMatches[1][0]);
                        $args[$wildKey] = $segmentsFromRequest[$key];
                    }

                    if ( ! $pathsMatch && empty($areWilds)) {
                        break;
                    }

                }

                if ($pathsMatch || ! empty($areWilds)) {
                    $this->foundRoute($requestPath, $handle, $args);
                    break;
                }

            }
        }

        return $this;
    }

    /**
     * Save Route If Found
     *
     * @param $requestPath
     * @param $handle
     * @param $args
     *
     * @return $this
     */
    public function foundRoute($requestPath, $handle, $args)
    {
        $this->match = [$requestPath, $handle, $args];

        return $this;
    }

    /**
     * @param $method
     *
     * @return array
     */
    public function getRegisteredRoutesByMethod($method)
    {
        $method = strtoupper($method);
        $routesRegistered = [];

        if (isset(self::$routes[$method])) {
            $routesRegistered = self::$routes[$method];
        }

        return $routesRegistered;
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    private function getPathSegments($path)
    {
        return explode('/', $path);
    }

}
