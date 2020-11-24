<?php
namespace TypeRocket\Utility;

class Str
{
    /**
     * @param string $str
     * @param string $delimiters
     *
     * @return false|string|string[]
     */
    public static function uppercaseWords($str, $delimiters = " \t\r\n\f\v" ) {
        return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * HTML class names helper
     *
     * @param string $defaults
     * @param null|array $classes
     * @param string $failed
     * @return string
     */
    public static function classNames($defaults, $classes = null, $failed = '') {
        if(!$result = Arr::reduceAllowedStr(is_array($defaults) ? $defaults : $classes)) {
            $result = !is_array($classes) ? $classes : $failed;
        }

        $defaults = !is_array($defaults) ? $defaults : '';

        return $defaults . ' ' . $result;
    }

    /**
     * Not blank string
     *
     * @param string|null $value
     *
     * @return bool
     */
    public static function notBlank($value) {
        return !(!isset($value) || $value === '');
    }

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
    public static function replaceFirstRegex($pattern, $new, $subject, $escape = true)
    {
        $pattern = $escape ? '/' . preg_quote($pattern, '/') . '/' : $pattern;
        return preg_replace($pattern, $new, $subject, 1);
    }

    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param  string  $search
     * @param  string  $new
     * @param  string  $subject
     * @return string
     */
    public static function replaceFirst($search, $new, $subject)
    {
        if ($search == '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $new, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param  string  $search
     * @param  string  $new
     * @param  string  $subject
     * @return string
     */
    public static function replaceLast($search, $new, $subject)
    {
        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $new, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * @param array|object $patterns
     * @param string $subject
     *
     * @return false|mixed
     */
    public static function pregMatchFindFirst(array $patterns, string $subject)
    {
        $regex = ['#^(?'];
        foreach ($patterns as $i => $pattern) {
            if($reg = is_string($pattern) ? $pattern : Data::walk(['regex'], $pattern)) {
                $regex[] = $reg . '(*MARK:'.$i.')';
            }
        }
        $regex = implode('|', $regex) . ')$#x';
        preg_match($regex, $subject, $m);

        if(empty($m)) { return null; }

        $found = isset($m['MARK']) && is_numeric($m['MARK']) ? $patterns[$m['MARK'] ] : null;
        if(empty($found)) { return null; }

        return $found;
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
        return $uppercase ? static::uppercaseWords($words) : $words;
    }

}