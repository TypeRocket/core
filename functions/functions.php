<?php

if( ! function_exists('str_starts') ) {
    /**
     * String starts with
     *
     * @param $needle
     * @param $subject
     *
     * @return bool
     */
    function str_starts( $needle, $subject ) {
        return \TypeRocket\Utility\Str::starts($needle, $subject);
    }
}


if( ! function_exists('str_ends') ) {
    /**
     * String ends with
     *
     * @param $needle
     * @param $subject
     *
     * @return bool
     */
    function str_ends( $needle, $subject ) {
        return \TypeRocket\Utility\Str::ends($needle, $subject);
    }
}

if( ! function_exists('str_contains') ) {
    /**
     * String ends with
     *
     * @param $needle
     * @param $subject
     *
     * @return bool
     */
    function str_contains( $needle, $subject ) {
        return \TypeRocket\Utility\Str::contains($needle, $subject);
    }
}

if( ! function_exists('dd') ) {
    /**
     * Die and Dump Vars
     *
     * @param $param
     */
    function dd($param) {
        call_user_func_array('var_dump', func_get_args());
        exit();
    }
}

if( ! function_exists('dots_walk') ) {
    /**
     * Dots Walk
     *
     * Traverse array with dot notation.
     *
     * @param string $dots dot notation key.next.final
     * @param array $array an array to traverse
     * @param null $default
     *
     * @return array|mixed|null
     */
    function dots_walk($dots, array $array, $default = null)
    {
        $traverse = explode('.', $dots);
        foreach ($traverse as $step) {
            if ( ! isset($array[$step]) && ! is_string($array)) {
                return $default;
            }
            $array = $array[$step];
        }

        return $array;
    }
}

if( ! function_exists('dots_set') ) {
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
    function dots_set($dots, array $array, $value)
    {
        $set      = &$array;
        $traverse = explode('.', $dots);
        foreach ($traverse as $step) {
            $set = &$set[$step];
        }
        $set = $value;

        return $array;
    }
}