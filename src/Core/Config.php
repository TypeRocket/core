<?php
namespace TypeRocket\Core;

use TypeRocket\Utility\Dots;

class Config
{

    static private $root;
    static private $config = [];

    /**
     * Set initial values
     *
     * @param string $root
     */
    public function __construct( $root )
    {
        self::$root = $root;
        self::$config['app'] = require( $root . '/app.php' );
        self::$config['typerocket'] = [
            'frontend' => false
        ];
    }

    /**
     * Get Root Location
     *
     * @return mixed
     */
    public static function getRoot()
    {
        return self::$root;
    }

    /**
     * Just In Time Config Loader
     *
     * @param string $dots
     * @param mixed $default
     *
     * @return array|mixed|null
     */
    private static function jitLocate($dots, $default)
    {
        list($root, $rest) = array_pad(explode('.', $dots, 2), 2, null);
        if(!isset(self::$config[$root])) {
            self::$config[$root] = require( self::$root . '/' . $root . '.php' );

            if(!$rest) {
                return self::$config[$root];
            }

            return Dots::walk($rest, self::$config[$root], $default);
        }

        return null;
    }

    /**
     * Locate Config Setting
     *
     * Traverse array with dot notation.
     *
     * @param string $dots dot notation key.next.final
     * @param null|mixed $default default value to return if null
     *
     * @return array|mixed|null
     */
    public static function locate($dots, $default = null)
    {
        $value = Dots::walk($dots, self::$config);
        if( isset($dots) && is_null($value) ) {
            return self::jitLocate($dots, $default);
        }

        return $value;
    }

    /**
     * Set Live TypeRocket Configs
     *
     * @param string $dots dot notation key.next.final
     * @param mixed $value
     *
     * @return array
     */
    public static function typerocket($dots, $value)
    {
        return Dots::set($dots, self::$config['typerocket'], $value);
    }

}
