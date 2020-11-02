<?php
namespace TypeRocket\Utility;

use App\Elements\Form;
use TypeRocket\Core\Config;
use TypeRocket\Elements\BaseForm;
use TypeRocket\Http\SSL;
use TypeRocket\Interfaces\Formable;

class Helper
{
    /**
     * Get WordPress Root
     *
     * @return string|false
     */
    public static function wordPressRootPath() {

        if( defined('TYPEROCKET_ROOT_WP') ) {
            return TYPEROCKET_ROOT_WP;
        }

        if( defined('TYPEROCKET_ROOT_INSTALL') ) {
            return TYPEROCKET_PATH . '/' . trim(Config::get('app.root.wordpress', 'wordpress'), '\\/');
        }

        if( defined('ABSPATH') ) {
            return ABSPATH;
        }

        $depth = TYPEROCKET_PATH;

        $looking = 5;
        while ($looking--) {
            if(is_file($depth . '/wp-load.php') && is_file($depth . '/wp-includes/wp-db.php')) {
                return $depth;
            }
            $depth .= '/..';
        }

        $galaxy_root = TYPEROCKET_PATH . '/' . trim(Config::get('app.root.wordpress'), '\\/');

        if(is_dir($galaxy_root)) {
            return $galaxy_root;
        }

        return false;
    }

    /**
     * Config URL
     *
     * @param string $path
     *
     * @return string
     */
    public static function assetsUrlBuild($path = '') {
        global $wp_actions;

        $path = trim('assets/' . ltrim($path, '/'), '/');
        $plugins_loaded = $url = null;

        if($wp_actions && isset($wp_actions['plugins_loaded'])) {
            $plugins_loaded = true;
        }

        if((defined('TYPEROCKET_THEME_INSTALL') || $plugins_loaded) && function_exists('get_theme_file_uri')) {
            $url = get_theme_file_uri( '/typerocket/wordpress/' . $path );
        }

        if(defined('TYPEROCKET_PLUGIN_INSTALL') && function_exists('plugins_url')) {
            $url = plugins_url( '/typerocket/wordpress/' . $path, TYPEROCKET_PATH );
        }

        if(defined('TYPEROCKET_ROOT_INSTALL')) {
            $url = home_url($path);
        }

        if(!$url || defined('TYPEROCKET_MU_INSTALL')) {
            $mu = typerocket_env('TYPEROCKET_MU_INSTALL', '/typerocket-plugin/typerocket/wordpress/');
            $url = WPMU_PLUGIN_URL . $mu . $path;
        }

        return SSL::fixSSLUrl($url);
    }

    /**
     * Get Assets URL
     *
     * @param string $append
     * @return string
     */
    public static function assetsUrl( $append ) {
        $root = Config::get('urls.assets');
        return $root . '/' . ltrim($append, '/');
    }

    /**
     * Get controller by recourse
     *
     * @param string $resource use the resource name to get controller
     * @param bool $instance
     *
     * @return null|string $controller
     */
    public static function controllerClass($resource, $instance = true)
    {
        if(is_string($resource) && $resource[0] == '@') {
            $resource = substr($resource, 1);
        }

        if(\TypeRocket\Utility\Str::ends('Controller', $resource)) {
            $resource = substr($resource, 0, -10);
        }

        $Resource = \TypeRocket\Utility\Str::camelize($resource);
        $controller    = \TypeRocket\Utility\Helper::appNamespace("Controllers\\{$Resource}Controller");
        return $instance ? new $controller : $controller;
    }

    /**
     * Get model by recourse
     *
     * @param string $resource use the resource name to get model
     * @param bool $instance
     *
     * @return null|string|\TypeRocket\Models\Model $object
     */
    public static function modelClass($resource, $instance = true)
    {
        if(is_string($resource) && $resource[0] == '@') {
            $resource = substr($resource, 1);
        }

        $Resource = \TypeRocket\Utility\Str::camelize($resource);
        $model    = \TypeRocket\Utility\Helper::appNamespace("Models\\{$Resource}");
        return $instance ? new $model : $model;
    }

    /**
     * Get Namespaced Class
     *
     * @param string|null $append
     * @return string
     */
    public static function appNamespace($append = null) {
        $space = $append ? "\\" . TYPEROCKET_APP_NAMESPACE . "\\" : TYPEROCKET_APP_NAMESPACE;
        return $space . ltrim($append, '\\');
    }

    /**
     * Get Numeric Hash
     *
     * This is not a real uuid. Will only generate a unique id per process.
     *
     * @return integer
     */
    public static function hash() {
        return wp_unique_id(time());
    }

    /**
     * Instance the From
     *
     * @param string|Formable|array|null $resource posts, users, comments, options your own
     * @param string|null $action update, delete, or create
     * @param null|int $item_id you can set this to null or an integer
     * @param mixed|null|string $model
     *
     * @return BaseForm|Form
     */
    public static function form($resource = null, $action = null, $item_id = null, $model = null)
    {
        $form = Config::get('app.class.form');
        return new $form(...func_get_args());
    }

    /**
     * Report Error
     *
     * @param \Throwable $exception
     * @param bool $debug
     * @return void
     */
    public static function reportError(\Throwable $exception, $debug = false) {
        $class = \TypeRocket\Core\Config::get('app.class.error', \TypeRocket\Utility\ExceptionReport::class);
        (new $class($exception, $debug))->report();
    }
}