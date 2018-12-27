<?php
namespace TypeRocket\Utility;

class Str
{

    /**
     * String Ends
     *
     * @param $needle
     * @param $subject
     *
     * @return bool
     */
    public static function ends( $needle, $subject )
    {
        $length = mb_strlen($needle);
        if ($length == 0) {
            return true;
        }

        return ( mb_substr($subject, -$length ) === $needle );
    }

    /**
     * String Contains
     *
     * @param $needle
     * @param $subject
     *
     * @return bool
     */
    public static function contains($needle, $subject)
    {
        return ( mb_strpos( $subject, $needle ) !== false );
    }

    /**
     * String Starts
     *
     * @param $needle
     * @param $subject
     *
     * @return bool
     */
    public static function starts($needle, $subject)
    {
        return mb_substr($subject, 0, mb_strlen($needle) ) === $needle;
    }

    /**
     * Convert To Camel Case
     *
     * @param string $input
     * @param string $separator specify - or _
     * @param bool $capitalize_first_char define as false if you want camelCase over CamelCase
     *
     * @return mixed
     */
    public static function camelize($input, $separator = '_', $capitalize_first_char = true)
    {
        $str = str_replace($separator, '', ucwords($input, $separator));

        if (!$capitalize_first_char) {
            $str = lcfirst($str);
        }

        return $str;
    }

    /**
     * Convert to Title Case
     *
     * 1. Call WP `sanitize_title`.
     * 2. Replace dash and underscore with space.
     * 3. Call `ucwords`.
     *
     * @param $string
     *
     * @return string
     */
     public static function title( $string )
     {
         return ucwords(
             str_replace(
                 ['-', '_'],
                 ' ',
                 sanitize_title( $string )
             )
         );
     }

}
