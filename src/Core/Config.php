<?php
namespace TypeRocket\Core;

use TypeRocket\Elements\Icons;

class Config
{

    static private $paths = null;
    static private $debug = false;
    static private $seed = null;
    static private $icons = null;
    static private $plugins = null;
    static private $frontend = false;

    /**
     * Set initial values
     */
    public function __construct()
    {
        if (self::$paths === null) {
            self::$debug   = defined( 'TR_DEBUG' ) ? TR_DEBUG : false;
            self::$seed    = defined( 'TR_SEED' ) ? TR_SEED : md5(NONCE_KEY);
            self::$plugins = defined( 'TR_PLUGINS' ) ? TR_PLUGINS : '';
            self::$icons   = defined( 'TR_ICONS' ) ? TR_ICONS : Icons::class;
            self::$paths   = $this->defaultPaths();
        }
    }

    /**
     * Get paths array
     *
     * @return mixed|null|void
     */
    static public function getPaths()
    {
        return self::$paths;
    }

    /**
     * Get debug status
     *
     * @return bool
     */
    static public function getDebugStatus()
    {
        return self::$debug;
    }

    /**
     * Get Seed
     *
     * @return null|string
     */
    static public function getSeed()
    {
        return self::$seed;
    }

    /**
     * Check TypeRocket for frontend
     *
     * @return null|string
     */
    static public function getFrontend()
    {
        return self::$frontend;
    }

    /**
     * Get array of plugins
     *
     * @return array
     */
    static public function getPlugins()
    {
        return explode( '|', self::$plugins );
    }

    /**
     * Set default paths
     *
     * @return array
     */
    private function defaultPaths()
    {
        return [
            'base'  => TR_PATH,
            'resource'  => TR_PATH . '/resource',
            'views'  => TR_PATH . '/views',
            'pages'  => TR_PATH . '/pages',
            'visuals'  => TR_PATH . '/visuals',
            'plugins' => TR_PATH . '/plugins',
            'components'  => TR_PATH . '/components',
            'app'  => TR_PATH . '/app',
            'urls' => [
                'theme'   => get_stylesheet_directory_uri(),
                'assets'  => TR_ASSETS_URL,
                'components' => TR_ASSETS_URL . '/components',
            ]
        ];
    }

    /**
     * Tell config that front end TypeRocket was enabled
     *
     * This action can not be undone
     */
    public static function enableFrontend()
    {
        self::$frontend = true;
    }

    /**
     * Get the icons class
     *
     * @return mixed
     */
    public static function getIcons()
    {
        return new self::$icons();
    }

    /**
     * Set Icons
     *
     * This action can not be undone
     *
     * @param string $class set the icon class
     */
    public static function setIcons( $class )
    {
        if( class_exists( $class ) ) {
            self::$icons = $class;
        }
    }

}
