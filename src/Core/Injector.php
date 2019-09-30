<?php


namespace TypeRocket\Core;


class Injector
{

    protected static $list = [];

    /**
     * Resolve Class
     *
     * @param string $class_name
     * @return mixed|null
     */
    public static function resolve($class_name) {
        if(array_key_exists($class_name, self::$list)) {
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

        return null;
    }

    /**
     * Register Class
     *
     * @param string $class_name
     * @param callable $callback
     * @param bool $singleton
     */
    public static function register($class_name, $callback, $singleton = false)
    {
        self::$list[$class_name] = [
            'callback' => $callback,
            'make_singleton' => $singleton,
            'singleton_instance' => null
        ];
    }

}