<?php
namespace TypeRocket\Utility;

use TypeRocket\Core\Resolver;

class Data
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
    public static function value($value, ?array $args = null)
    {
        return $value instanceof \Closure ? (new Resolver)->resolveCallable($value, $args) : $value;
    }

    /**
     * Nil
     *
     * @param array|object $value
     * @return Nil
     */
    public static function nil($value)
    {
        if($value instanceof Nil) {
            return new Nil($value->get());
        }

        return new Nil($value);
    }

    /**
     * Map Deep
     *
     * Maps a function to all non-iterable elements of an array or an object. This
     * is similar to `array_walk_recursive()` but acts upon objects too.
     *
     * @param callable $callback The function to map onto $value.
     * @param mixed    $value    The array, object, or scalar.
     *
     * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
     */
    public static function mapDeep(callable $callback, $value)
    {
        return map_deep($value, $callback);
    }

    /**
     * Map
     *
     * Maps a function to a value. When an array or object is
     * given it iterates over the indexes or properties.
     *
     * @param callable $callback The function to map onto $value.
     * @param mixed    $value    The array, object, or scalar.
     *
     * @return mixed The value with the callback applied.
     */
    public static function map(callable $callback, $value)
    {
        if ( is_array( $value ) ) {
            $value = array_map( $callback, $value );
        } elseif ( is_object( $value ) ) {
            $object_vars = get_object_vars( $value );
            foreach ( $object_vars as $property_name => $property_value ) {
                $value->$property_name = call_user_func( $callback, $property_value );
            }
        } else {
            $value = call_user_func( $callback, $value );
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public static function emptyRecursive($value) : bool
    {
        if (is_array($value)) {
            $empty = true;
            array_walk_recursive($value, function($item) use (&$empty) {
                $empty = $empty && empty($item);
            });
        } else {
            $empty = empty($value);
        }
        return $empty;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public static function emptyOrBlankRecursive($value) : bool
    {
        if (is_array($value)) {
            $empty = true;
            array_walk_recursive($value, function($item) use (&$empty) {
                if(is_string($item) || is_int($item)) {
                    $empty = !Str::notBlank($item);
                } else {
                    $empty = $empty && empty($item);
                }
            });
        } else {
            if(is_string($value)|| is_int($value)) {
                $empty = !Str::notBlank($value);
            } else {
                $empty = empty($value);
            }
        }
        return $empty;
    }

    /**
     * Dots Walk
     *
     * Traverse array with dot notation with wilds (*).
     *
     * @param string|array $dots dot notation key.next.final
     * @param array|object $array an array to traverse
     * @param null|mixed $default
     *
     * @return array|mixed|null
     */
    public static function walk($dots, $array, $default = null)
    {
        $traverse = is_array($dots) ? $dots : explode('.', $dots);
        foreach ($traverse as $i => $step) {
            unset($traverse[$i]);
            if($step === '*' && is_array($array)) {
                return array_map(function($item) use ($traverse, $default) {
                    return static::walk($traverse, $item, $default);
                }, $array);
            } else {
                $v = is_object($array) ? ($array->$step ?? null) : ($array[$step] ?? null);
            }

            if ( !isset($v) && ! is_string($array) ) {
                return $default;
            }
            $array = $v ?? $default;
        }

        return $array;
    }

    /**
     * Get
     *
     * Get value using dot notation without wilds (*).
     *
     * @param object|array $data an array to traverse
     * @param string|array $dots dot notation key.next.final or array of dots
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function get($data, $dots, $default = null)
    {
        $dots = (array) $dots;
        $index = count($dots) > 1;
        $return = null;

        foreach ($dots as $dot) {
            $traverse = explode('.', $dot);
            $search = $data;

            foreach ($traverse as $step) {
                $v = is_object($search) ? ($search->$step ?? null) : ($search[$step] ?? null);

                if ( !isset($v) && ! is_string($search) ) {
                    $search = $default;
                    break;
                }
                $search = $v ?? $default;
            }

            if($index) {
                $return[$dot] = $search;
            } else {
                $return = $search;
            }
        }

        return $return;
    }

    /**
     * Index Data by Unique Value
     *
     * @param string $index
     * @param iterable|array<int, object|array> $array an array to traverse with object or arrays
     * @param bool $unique
     *
     * @return array<int, object|array>
     * @throws \Exception
     */
    public static function createMapIndexBy(string $index, $array, $unique = true) : array
    {
        $indexed_list = [];

        foreach ($array as $item) {
            if(!is_array($item) && !is_object($item)) {
                $type = gettype($item);
                throw new \Exception("Nested array or object required for Data::createMapIndexBy(): {$type} is not valid.");
            }

            $key = is_object($item) ? ($item->$index ?? null) : ($item[$index] ?? null);

            if($unique && array_key_exists($key, $indexed_list)) {
                throw new \Exception("Array key must be unique for Data::createMapIndexBy(): {$key} already taken.");
            }

            $indexed_list[$key] = $item;
        }

        return $indexed_list;
    }

    /**
     * @param mixed $value
     * @param string|callable $type
     *
     * @return bool|float|int|mixed|string
     */
    public static function cast($value, $type)
    {
        // Integer
        if ($type == 'int' || $type == 'integer') {
            return is_object($value) || is_array($value) ? null : (int) $value;
        }

        // Float
        if ($type == 'float' || $type == 'double' || $type == 'real') {
            return is_object($value) || is_array($value) ? null : (float) $value;
        }

        // JSON
        if ($type == 'json') {

            if(is_serialized($value)) {
                $value = unserialize($value);
            } if(static::isJson($value)) {
                return $value;
            }

            return json_encode($value);
        }

        // Serialize
        if ($type == 'serialize' || $type == 'serial') {

            if(static::isJson($value)) {
                $value = json_decode((string) $value, true);
            } if(is_serialized($value)) {
                return $value;
            }

            return serialize($value);
        }

        // String
        if ($type == 'str' || $type == 'string') {
            if(is_object($value) || is_array($value)) {
                $value = json_encode($value);
            } else {
                $value = (string) $value;
            }

            return $value;
        }

        // Bool
        if ($type == 'bool' || $type == 'boolean') {
            return (bool) $value;
        }

        // Array
        if ($type == 'array') {
            if(is_numeric($value)) {
                return $value;
            } elseif (is_string($value) && static::isJson($value)) {
                $value = json_decode($value, true);
            } elseif (is_string($value) && is_serialized($value)) {
                $value = unserialize($value);
            } elseif(!is_string($value)) {
                $value = (array) $value;
            } elseif (trim($value) == '""') {
                $value = null;
            }

            return $value;
        }

        // Object
        if ($type == 'object' || $type == 'obj') {
            if(is_numeric($value)) {
                return $value;
            } elseif (is_string($value) && static::isJson($value)) {
                $value = (object) json_decode($value);
            } elseif (is_string($value) && is_serialized($value)) {
                $value = (object) unserialize($value);
            } elseif(!is_string($value)) {
                $value = (object) $value;
            } elseif (is_array($value)) {
                $value = (object) $value;
            } elseif (trim($value) == '""') {
                $value = null;
            }

            return $value;
        }

        // Callback
        if (is_callable($type)) {
            return call_user_func($type, $value);
        }

        return $value;
    }

    /**
     * Detect is JSON
     *
     * @param $args
     *
     * @return bool
     */
    public static function isJson(...$args)
    {
        if(is_array($args[0]) || is_object($args[0])) {
            return false;
        }

        $s = trim($args[0]);

        if($s === '' || $s === '""') {
            return false;
        }

        json_decode(...$args);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}