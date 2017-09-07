<?php

namespace TypeRocket\Utility;

class Dots
{
    /**
     * Dots Walk
     *
     * Traverse array with dot notation.
     *
     * @param string $dots dot notation key.next.final or key.*.final
     * @param array $array an array to traverse
     *
     * @return array|mixed|null
     */
    public static function walk($dots, array $array) {
        $traverse = explode('.', $dots);
        foreach ($traverse as $step) {
            if( ! isset($array[$step]) && ! is_string($array) ) { return null; }
            $array = $array[$step];
        }
        return $array;
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
        $set = &$array;
        $traverse = explode('.', $dots);
        foreach($traverse as $step) {
            $set = &$set[$step];
        }
        $set = $value;
        return $array;
    }
}