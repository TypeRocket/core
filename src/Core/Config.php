<?php
namespace TypeRocket\Core;

use TypeRocket\Utility\Data;

class Config
{
    public const ALIAS = 'config';

    protected $root;
    protected $config = [];

    /**
     * Set initial values
     *
     * @param string $root
     * @param array $overrides
     */
    public function __construct( $root, $overrides = [] )
    {
        $this->root = $root;
        $this->config = $overrides;
    }

    /**
     * Get Root Location
     *
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Just In Time Config Loader
     *
     * @param string $dots
     * @param mixed $default
     *
     * @return array|mixed|null
     */
    private function jitLocate($dots, $default = null)
    {
        [$root, $rest] = array_pad(explode('.', $dots, 2), 2, null);
        if(!isset($this->config[$root]) && is_file($this->root . '/' . $root . '.php')) {
            /** @noinspection PhpIncludeInspection */
            $this->config[$root] = require( $this->root . '/' . $root . '.php' );

            if(!$rest) {
                return $this->config[$root];
            }

            return Data::walk($rest, $this->config[$root], $default);
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
    public function locate($dots, $default = null)
    {
        $value = Data::walk($dots, $this->config);
        if( isset($dots) && is_null($value) ) {
            return self::jitLocate($dots, $default);
        }

        return $value ?? $default;
    }

    /**
     * Get Constant Variable
     *
     * @param string $name the constant variable name
     * @param null|mixed $default The default value
     * @param bool $env Try getting env data
     *
     * @return mixed
     */
    public static function env(string $name, $default = null, $env = false)
    {
        if($env && !empty($_SERVER) ) {
            $env = $_SERVER[$name] ?? null;

            if($env) {
                return $env;
            }
        }

        return defined($name) ? constant($name) : $default;
    }

    /**
     * @return static
     */
    public static function getFromContainer()
    {
        return Container::resolve(static::ALIAS);
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
    public static function get($dots, $default = null) {
        return static::getFromContainer()->locate($dots, $default);
    }
}
