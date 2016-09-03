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

}