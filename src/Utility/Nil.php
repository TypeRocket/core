<?php
namespace TypeRocket\Utility;

use ArrayAccess;

class Nil implements ArrayAccess
{
    /** @var mixed */
    protected $value;

    /**
     * @param mixed $value
     *
     * @return void
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    /**
     * @return mixed|null
     */
    public function get()
    {
        return $this->value instanceof Nil ? $this->value->get() : $this->value;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return new Nil($this->value->{$key} ?? new Nil);
    }

    /**
     * @param mixed $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        if (is_object($this->value)) {
            return isset($this->value->{$name});
        }

        if ($this->arrayCheck()) {
            return isset($this->value[$name]);
        }

        return false;
    }

    /**
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->arrayCheck($key);
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return new Nil($this->value[$key] ?? new Nil);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if ($this->arrayCheck()) {
            $this->value[$key] = $value;
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        if ($this->arrayCheck()) {
            unset($this->value[$key]);
        }
    }

    /**
     * @param null|string $key
     *
     * @return bool
     */
    protected function arrayCheck($key = null) {
        if(is_array($this->value) || $this->value instanceof ArrayAccess) {

            if($key && !($this->value[$key] ?? null)) {
                return false;
            }

            return true;
        }

        return false;
    }
}