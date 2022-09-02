<?php
namespace TypeRocket\Utility;

class Arr
{
    /**
     * Is Empty Array
     *
     * @param array $array
     *
     * @return bool
     */
    public static function isEmptyArray($array) : bool
    {
        return $array === [];
    }

    /**
     * Filter Null Values Only
     *
     * @param array $list
     *
     * @return array
     */
    public static function filterNull(array $list) : array
    {
        return array_filter($list, function($v, $k) {
            return !is_null($v);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @param array $array
     * @param string|array $columns
     *
     * @return array
     */
    public static function only(array $array, $columns) : array
    {
        $values = [];
        $columns = (array) $columns;

        foreach($columns as $column) {
            $values[$column] = Data::walk($column, $array) ?? null;
        }

        return static::meldExpand($values);
    }

    /**
     * Pluck Value(s) and/or Index Them
     *
     * @param array $array
     * @param string|array $columns
     * @param string|null $index
     *
     * @return array
     */
    public static function pluck(array $array, $columns, ?string $index = null) : array
    {
        $list = [];
        $columns = (array) $columns;

        if(count($columns) > 1) {
            $cb = function($item, $columns) {
                return static::only($item, $columns);
            };
        } else {
            $cb = function($item, $columns) {
                return Data::walk($columns, $item);
            };
        }

        foreach ($array as $item) {

            $item_value = $cb($item, $columns);

            if($index) {
                $index_key = Data::walk($index, $item);

                if(array_key_exists($index_key, $list)) {
                    throw new \Exception('Array key must be unique for Arr::pluck with index.');
                }

                $list[$index_key] = $item_value;
            } else {
                $list[] = $item_value;
            }
        }

        return $list;
    }

    /**
     * Maps a function to all non-iterable elements of an array or an object.
     *
     * This is similar to `array_walk_recursive()` but acts upon objects too.
     *
     * @param callable $callback The function to map onto $value.
     * @param mixed    $value    The array, object, or scalar.
     * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
     */
    public static function mapDeep(callable $callback, $value)
    {
        return Data::mapDeep($callback, $value);
    }

    /**
     * Index Array by Unique Value
     *
     * @param string $index
     * @param array $array
     * @param bool $unique
     *
     * @return array
     * @throws \Exception
     */
    public static function indexBy(string $index, array $array, $unique = true) : array
    {
        $indexed_list = [];

        foreach ($array as $item) {
            if(!is_array($item) || ($unique && array_key_exists($item[$index], $indexed_list))) {
                throw new \Exception('Array list required and array key must be unique for Arr::indexBy.');
            }

            $indexed_list[$item[$index]] = $item;
        }

        return $indexed_list;
    }

    /**
     * Replace Recursive Prefer New
     *
     * Works like array_replace_recursive but keeps the order of the new array
     * and allows for setting stop break points to the replacement search.
     *
     * @param array $current_array array to replace data within
     * @param array $new_array array to keep all data from
     * @param array $stops dot notation list of merge stop points where new_array when present will override deep values
     *
     * @return array
     */
    public static function replaceRecursivePreferNew($current_array, $new_array, array $stops = []) : array
    {
        if(!empty($stops)) {
            foreach ($stops as $dots)
            {
                $current_ref = &$current_array;
                $new_ref = &$new_array;

                $seek = explode('.', $dots);
                $miss = false;
                foreach ($seek as $index)
                {
                    if(array_key_exists($index, $current_ref) && array_key_exists($index, $new_ref)) {
                        $current_ref = &$current_ref[$index];
                        $new_ref = &$new_ref[$index];
                        continue;
                    }

                    $miss = true;
                    break;
                }

                if(!$miss && isset($new_ref)) {
                    $current_ref = $new_ref;
                }
            }
        }

        return array_replace_recursive($new_array, $current_array, $new_array);
    }

    /**
     * Dots Meld
     *
     * Flatten array dimensions to one level and meld keys into dot
     * notation. liken meld to ['key.child' => 'value'].
     *
     * @param array $array the values to meld
     *
     * @return array
     */
    public static function meld(array $array) : array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
        $result = [];
        foreach ($iterator as $value) {
            $keys = [];
            $depth = range(0, $iterator->getDepth());
            foreach ($depth as $step) {
                $keys[] = $iterator->getSubIterator($step)->key();
            }
            $result[ implode('.', $keys) ] = $value;
        }

        return $result;
    }

    /**
     * Dots Meld Expand
     *
     * Expand a dots melded array into a multi-dimensional array.
     *
     * @param array $array dots melded array to expand
     *
     * @return array
     */
    public static function meldExpand( array $array ) : array {
        $expand = [];
        foreach ($array as $dots => $value ) {
            $traverse = explode('.', $dots);
            $set = &$expand;
            foreach ($traverse as $key) {
                $set = &$set[$key];
            }
            $set = $value;
        }

        return $expand;
    }

    /**
     * Array Get
     *
     * Strictly get a value from an array using dot notation without wilds (*).
     *
     * @param array|\ArrayAccess $array Array to search.
     * @param string|array $needle Value to check in dot notation, or an array of string values.
     * @param mixed $default Fallback if value is null.
     */
    public static function get($array, $needle, $default = null)
    {
        $search = is_array($needle) ? $needle : explode('.', $needle);

        foreach ($search as $index) {
            if(is_array($array) && array_key_exists($index, $array)) {
                $array = $array[$index];
            }
            else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Array Has
     *
     * Strictly check if dot notation without wilds (*) index exists.
     *
     * @param array|\ArrayAccess $array Array to search.
     * @param string|array $needle Value to check in dot notation, or an array of string values.
     */
    public static function has($array, $needle)
    {
        $needles = (array) $needle;
        $main = $array;

        foreach ($needles as $n) {

            $search = is_array($n) ? $n : explode('.', $n);

            foreach ($search as $index) {
                if(is_array($array) && array_key_exists($index, $array)) {
                    $array = $array[$index];
                }
                elseif($array instanceof \ArrayAccess && $array->offsetExists($index)) {
                    $array = $array[$index];
                }
                else {
                    return false;
                }
            }

            $array = $main;
        }

        return true;
    }

    /**
     * Dots Set
     *
     * Set an array value using dot notation.
     *
     * @param string $dots dot notation path to set
     * @param array $array the original array
     * @param mixed $value the value to set
     *
     * @return array
     */
    public static function set($dots, array $array, $value)
    {
        $set      = &$array;
        $traverse = explode('.', $dots);
        foreach ($traverse as $step) {
            $set = &$set[$step];
        }
        $set = $value;

        return $array;
    }

    /**
     * HTML class names helper
     *
     * @param array $array
     *
     * @return string
     */
    public static function reduceAllowedStr($array) {
        $reduced = '';
        array_walk($array, function($val, $key) use(&$reduced) {
            $reduced .= $val ? " $key" : '';
        });
        $cleaned = implode(' ', array_unique(array_map('trim', explode(' ', trim($reduced)))));
        return $cleaned;
    }

    /**
     * Used to format fields
     *
     * @param string|array $dots
     * @param array|\ArrayObject $arr
     * @param string|callable $callback
     *
     * @return array|null
     */
    public static function format($dots, &$arr, $callback)
    {
        $loc = &$arr;
        $search = is_array($dots) ? $dots : explode('.', $dots);
        foreach($search as $i => $step)
        {
            array_shift($search);
            if($step === '*' && is_array($loc)) {
                $new_loc = &$loc;
                $indies = array_keys($new_loc);
                foreach($indies as $index) {
                    if(isset($new_loc[$index])) {
                        static::format($search, $new_loc[$index], $callback);
                    }
                }
            } elseif( isset($loc[$step] ) ) {
                $loc = &$loc[$step];
            } else {
                return null;
            }
        }

        if(!isset($indies) && is_callable($callback)) {
            $loc = call_user_func($callback, $loc);
        }

        return $loc;
    }

    /**
     * Is Array Sequential
     *
     * @param array $array The array being evaluated.
     * @return bool
     */
    public static function isSequential(array $array) : bool
    {
        if ([] === $array) return false;
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Is Array Associative
     *
     * @param array $array The array being evaluated.
     * @return bool
     */
    public static function isAssociative(array $array) : bool
    {
        if ([] === $array) return false;
        return !static::isSequential($array);
    }

    /**
     * Is Array Accessible
     *
     * @param mixed $var The variable being evaluated.
     */
    public static function isAccessible($var) : bool
    {
        return is_array($var) || $var instanceof \ArrayAccess;
    }

    /**
     * Array Divide
     *
     * Return key and values as separate arrays.
     *
     * @param array $array
     * @return array
     */
    public static function divide(array $array) : array
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Array Partition
     *
     * Partition and spread array semi-evenly across a number of groups.
     *
     * @param array $array The array being evaluated.
     * @param int $groups The number of groups.
     */
    public static function partition(array $array, int $groups) : array
    {
        $count = count( $array );
        $parts = floor( $count / $groups );
        $rem = $count % $groups;
        $partition = [];
        $mark = 0;
        for ($index = 0; $index < $groups; $index++)
        {
            $incr = ($index < $rem) ? $parts + 1 : $parts;
            $partition[$index] = array_slice( $array, $mark, $incr );
            $mark += $incr;
        }
        return $partition;
    }

    /**
     * Array Keys Exist
     *
     * @param array $keys an array of key names
     * @param array $array the array to check
     *
     * @return bool
     */
    public static function keysExist(array $keys, array $array)
    {
        return !array_diff_key(array_flip($keys), $array);
    }

    /**
     * Exists
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key) : bool
    {
        if (is_float($key)) {
            $key = (string) $key;
        }

        if($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * First Item
     *
     * @param array|\ArrayObject $array
     * @return mixed|null
     */
    public static function first($array, $callback = null, $default = null)
    {
        if(is_callable($callback))
        {
            foreach ($array as $key => $value) {
                if($callback($value, $key) === true) {
                    return $value;
                }
            }
        }
        else
        {
            foreach ($array as $item) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Last Item
     *
     * @param array|\ArrayObject $array
     * @return mixed|null
     */
    public static function last($array, $callback = null, $default = null)
    {
        return static::first(array_reverse((array) $array, true), $callback, $default);
    }
}