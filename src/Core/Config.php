<?php
namespace TypeRocket\Core;

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
        list($root, $rest) = array_pad(explode('.', $dots, 2), 2, null);
        if(!isset($this->config[$root]) && is_file($this->root . '/' . $root . '.php')) {
            /** @noinspection PhpIncludeInspection */
            $this->config[$root] = require( $this->root . '/' . $root . '.php' );

            if(!$rest) {
                return $this->config[$root];
            }

            return dots_walk($rest, $this->config[$root], $default);
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
        $value = dots_walk($dots, $this->config);
        if( isset($dots) && is_null($value) ) {
            return self::jitLocate($dots, $default);
        }

        return $value ?? $default;
    }

    /**
     * @return static
     */
    public static function getFromContainer()
    {
        return tr_container(static::ALIAS);
    }
}
