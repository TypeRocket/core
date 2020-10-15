<?php
namespace TypeRocket\Utility;

use TypeRocket\Core\Container;

class RuntimeCache
{
    public const ALIAS = 'cache';
    public $cache = [ 'typerocket' => [] ];

    /**
     * @param string $key
     * @param $data
     * @param string $namespace
     *
     * @return $this
     * @throws \Exception
     */
    public function add($key, $data, $namespace = 'typerocket')
    {
        if( !empty($this->cache[$namespace][$key]) ) {
            throw new \Exception("Runtime cache already set. namespace:{$namespace} key:{$key}");
        }

        $this->cache[$namespace][$key] = $data;

        return $this;
    }

    /**
     * @param string $key
     * @param $data
     * @param string $namespace
     *
     * @return $this
     */
    public function update($key, $data, $namespace = 'typerocket')
    {
        $this->cache[$namespace][$key] = $data;

        return $this;
    }

    /**
     * @param string $key
     * @param string $namespace
     *
     * @return array|mixed|null
     */
    public function get($key, $namespace = 'typerocket')
    {
        return Data::walk($key, $this->cache[$namespace]);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @param null|string $namespace
     *
     * @return $this|array|mixed|null
     * @throws \ReflectionException
     */
    public function getOtherwisePut($key, $default = null, $namespace = null)
    {
        if($value = $this->get($key, $namespace)) {
            return $value;
        }

        return $this->add($key, Value::get($default), $namespace);
    }

    /**
     * @param string $key
     * @param string $namespace
     *
     * @return $this
     */
    public function delete($key, $namespace = 'typerocket')
    {
        $this->cache[$namespace][$key] = null;

        return $this;
    }

    /**
     * @return static
     */
    public static function getFromContainer()
    {
        return Container::resolve(static::ALIAS);
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     * @throws \Exception
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }
}