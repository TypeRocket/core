<?php
namespace TypeRocket\Utility;

class Arr
{
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
}