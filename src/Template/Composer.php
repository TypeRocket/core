<?php
namespace TypeRocket\Template;

use TypeRocket\Models\Model;

abstract class Composer
{
    /** @var array|object|Model $data */
    protected $data;
    protected $type;

    /**
     * @param array|object|Model $data
     *
     * @return static
     */
    public static function new($data)
    {
        return new static($data);
    }

    /**
     * @param array|object|Model $data
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->type = is_array($this->data) ? 'array' : 'object';
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function __get($key)
    {
        return $this->type == 'array' ? ($this->data[$key] ?? null) : ($this->data->{$key} ?? null);
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->data->{$name}(...$arguments);
    }
}