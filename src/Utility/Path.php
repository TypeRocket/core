<?php
namespace TypeRocket\Utility;

/**
 * Class Path
 *
 * @method static string views(string $append)
 * @method static string app(string $append)
 * @method static string storage(string $append)
 * @method static string cache(string $append)
 * @method static string resources(string $append)
 * @method static string themes(string $append)
 * @method static string routes(string $append)
 * @method static string migrations(string $append)
 * @method static string vendor(string $append)
 * @method static string core(string $append)
 * @method static string pro(string $append)
 * @method static string assets(string $append)
 *
 * @package TypeRocket\Utility
 */
class Path
{
    /**
     * Get TypeRocket Directory
     *
     * @param string $append
     * @return string
     */
    public static function get($context, $append) {
        $root = \TypeRocket\Core\Config::get('paths.' . $context);
        return $root . '/' . ltrim($append, '/');
    }

    /**
     * Get Directory
     *
     * @param string $name
     * @param array $args
     *
     * @return string
     */
    public static function __callStatic( $name, $args )
    {
        return static::get($name, ...$args);
    }
}