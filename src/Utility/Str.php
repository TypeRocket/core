<?php
namespace TypeRocket\Utility;

class Str
{

    /**
     * String Ends
     *
     * @param string $needle
     * @param string $subject
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
     * @param string $needle
     * @param string $subject
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
     * @param string $needle
     * @param string $subject
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
     * Trim Start
     *
     * @param string $subject
     * @param string $trim
     * @return string
     */
    public static function trimStart($subject, $trim = '/')
    {
        if (substr($subject, 0, strlen($trim)) == $trim) {
            $subject = substr($subject, strlen($trim));
        }

        return $subject;
    }

}