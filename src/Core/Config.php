<?php
namespace TypeRocket\Core;

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
        if(self::$root) {
            return;
        }

        self::$root = $root;
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
    private static function jitLocate($dots, $default = null)
    {
        list($root, $rest) = array_pad(explode('.', $dots, 2), 2, null);
        if(!isset(self::$config[$root]) && is_file(self::$root . '/' . $root . '.php')) {
            self::$config[$root] = require( self::$root . '/' . $root . '.php' );

            if(!$rest) {
                return self::$config[$root];
            }

            return dots_walk($rest, self::$config[$root], $default);
        }

        return $default;
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
        $value = dots_walk($dots, self::$config);
        if( isset($dots) && is_null($value) ) {
            return self::jitLocate($dots, $default);
        }

        return $value ?? $default;
    }
}
