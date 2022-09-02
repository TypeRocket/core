<?php
namespace TypeRocket\Utility;

/**
 * @deprecated
 */
class Value
{
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param null|array $args
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public static function get($value, $args = null)
    {
        return Data::value($value, $args);
    }

    /**
     * Provide access to optional objects.
     *
     * @param array|object|\ArrayObject $value
     * @return Nil
     */
    public static function nils($value)
    {
        return Data::nil($value);
    }

    /**
     * @param object|array $data an array to traverse
     * @param string|array $dots dot notation key.next.final
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function data($data, $dots, $default = null)
    {
        return Data::get(...func_get_args());
    }
}