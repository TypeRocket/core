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
     * Snake Case
     *
     * @param $input
     *
     * @return string
     */
    public static function snake($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
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

    /**
     * Replace First
     *
     * @param string $pattern
     * @param string $new
     * @param string $subject
     * @param bool $escape
     * @return string|string[]|null
     */
    public static function replaceFirst($pattern, $new, $subject, $escape = true)
    {
        $pattern = $escape ? '/' . preg_quote($pattern, '/') . '/' : $pattern;
        return preg_replace($pattern, $new, $subject, 1);
    }

    /**
     * Split At
     *
     * @param $pattern
     * @param $subject
     * @param bool $last
     * @return array
     */
    public static function splitAt($pattern, $subject, $last = false)
    {
        if(!$last) {
            return array_pad(explode($pattern, $subject, 2), 2, null);
        }

        $parts = explode($pattern, $subject);
        $last = array_pop($parts);
        $first = implode($pattern, $parts);
        return [$first ?: null, $last];
    }

    /**
     * Make Words
     *
     * @param string $subject
     * @param bool $uppercase
     * @param string $separator
     * @return mixed|string
     */
    public static function makeWords($subject, $uppercase, $separator = '_')
    {
        $words = str_replace($separator, ' ', $subject);
        return $uppercase ? ucwords($words) : $words;
    }

}