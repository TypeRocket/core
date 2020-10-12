<?php
namespace TypeRocket\Utility;

use TypeRocket\Core\Resolver;

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
        return $value instanceof \Closure ? (new Resolver)->resolveCallable($value, $args) : $value;
    }

    /**
     * Provide access to optional objects.
     *
     * @param array|object|\ArrayObject $value
     * @return Nil
     */
    public static function nils($value)
    {
        if($value instanceof Nil) {
            return new Nil($value->get());
        }

        return new Nil($value);
    }

    /**
     * @param object|array $data an array to traverse
     * @param string $dots dot notation key.next.final
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function data($data, $dots, $default = null)
    {
        $traverse = explode('.', $dots);
        foreach ($traverse as $step) {
            $v = is_object($data) ? ($data->$step ?? null) : ($data[$step] ?? null);

            if ( !isset($v) && ! is_string($data) ) {
                return $default;
            }
            $data = $v ?? $default;
        }

        return $data;
    }
}