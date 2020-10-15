<?php
namespace TypeRocket\Core;

class Container
{
    protected static $list = [];
    protected static $alias = [];

    /**
     * Resolve Class
     *
     * @param string $class_name class name or alias
     * @param bool $forceAliasLookup only check for instance by alias
     *
     * @return mixed|null
     */
    public static function resolve($class_name, $forceAliasLookup = false) {
        if(!$forceAliasLookup && array_key_exists($class_name, self::$list)) {
            $single = self::$list[$class_name]['singleton_instance'];

            if($single) {
                return $single;
            }

            $instance = call_user_func(self::$list[$class_name]['callback']);

            if(!empty(self::$list[$class_name]['make_singleton'])) {
                self::$list[$class_name]['singleton_instance'] = $instance;
            }

            return $instance;
        }

        return self::resolveAlias($class_name);
    }

    /**
     * Resolve by Alias Only
     *
     * @param string $alias alias
     *
     * @return mixed|null
     */
    public static function resolveAlias($alias) {
        if(!empty(self::$alias[$alias])) {
            return self::resolve(self::$alias[$alias]);
        }

        return null;
    }

    /**
     * Register Class
     *
     * @param string $class_name
     * @param callable $callback
     * @param bool $singleton
     * @param null|string $alias
     * @return bool
     */
    public static function register($class_name, $callback, $singleton = false, $alias = null)
    {
        if(!empty(self::$list[$class_name])) {
            return false;
        }

        self::$list[$class_name] = [
            'callback' => $callback,
            'make_singleton' => $singleton,
            'singleton_instance' => null
        ];

        if($alias && empty(self::$alias[$alias])) {
            self::$alias[$alias] = $class_name;
        }

        return true;
    }

    /**
     * Register Singleton
     *
     * @param string $class_name
     * @param callable $callback
     * @param null|string  $alias
     * @return bool
     */
    public static function singleton($class_name, $callback, $alias = null)
    {
        return self::register($class_name, $callback, true, $alias);
    }

    /**
     * Get Aliases
     *
     * @return array
     */
    public function aliases()
    {
        return self::$alias;
    }

    /**
     * Resolve Singleton
     *
     * @param string $class_name
     * @param null|string $alias
     *
     * @return mixed|null
     */
    public static function findOrNewSingleton($class_name, $alias = null)
    {
        self::register($class_name, function() use ($class_name) {
            return new $class_name;
        }, true, $alias);

        return self::resolve($class_name);
    }

}