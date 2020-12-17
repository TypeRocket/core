<?php
namespace TypeRocket\Utility;

class Dump
{
    /**
     * @param mixed ...$args
     */
    public static function die(...$args)
    {
        static::data(...$args);
        die();
    }

    /**
     * @param mixed ...$args
     */
    public static function data(...$args)
    {
        var_dump(...$args);
    }
}