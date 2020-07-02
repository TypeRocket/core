<?php

if( ! function_exists('str_starts') ) {
    /**
     * String starts with
     *
     * @param string $needle
     * @param string $subject
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
     * @param string $needle
     * @param string $subject
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
     * @param string $needle
     * @param string $subject
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
     * @param string $param
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

if ( ! function_exists('immutable')) {
    /**
     * Get Constant Variable
     *
     * @param string $name the constant variable name
     * @param null|mixed $default The default value
     *
     * @return mixed
     */
    function immutable($name, $default = null) {
        return defined($name) ? constant($name) : $default;
    }
}
if ( ! function_exists('resolve_class')) {
    /**
     * Resolve Class
     *
     * @param string $class
     *
     * @return object
     * @throws \Exception
     * @deprecated 4.0.53
     */
    function resolve_class(string $class)
    {
        $reflector = new \ReflectionClass($class);
        if ( ! $reflector->isInstantiable()) {
            throw new \Exception($class . ' is not instantiable');
        }
        if ( ! $constructor = $reflector->getConstructor()) {
            return new $class;
        }
        // Get Dependencies
        $dependencies = [];
        $parameters   = $constructor->getParameters();
        // Auto Fill Parameters
        foreach ($parameters as $parameter) {
            if ( ! $dependency = $parameter->getClass()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception('Cannot resolve no default for ' . $parameter->name . ' in ' . $class);
                }
            } else {
                $dependencies[] = resolve_class($dependency->name);
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}

if ( ! function_exists('resolve_method_args')) {
    /**
     * Resolve Call
     *
     * @param mixed $call the function or object map to resolve
     * @param array $map the values to map parameters
     * @param bool $stub create stub of class if found
     *
     * @return mixed
     */
    function resolve_method_args($call, $map = [], $stub = true)
    {
        try {
            if (is_array($call)) {
                $ref = new \ReflectionMethod($call[0], $call[1]);
            } else {
                $ref = new \ReflectionFunction($call);
            }
            $params = $ref->getParameters();
            $args   = [];
            foreach ($params as $param) {
                $default = null;
                $class   = $param->getClass() ?? null;
                if ($class) {
                    $class = (new \TypeRocket\Core\Resolver())->resolve($class->getName());
                }
                if ($param->isDefaultValueAvailable()) {
                    $default = $param->getDefaultValue();
                }
                $param_name = $param->getName();
                $loaded_obj = isset($map[$param_name]) && is_object($map[$param_name]) ? $map[$param_name] : null;
                $args[]     = $loaded_obj ?? $class ?? $map[$param_name] ?? $default ?? null;
            }
            $method_map = ['args' => $args, 'method' => $ref, 'caller' => $call];
            if ($stub && $ref instanceof \ReflectionMethod) {
                if ( ! is_object($call[0])) {
                    $method_map['caller'][] = (new \TypeRocket\Core\Resolver())->resolve($call[0]);
                } else {
                    $method_map['caller'][] = $call[0];
                }
            }
        } catch (\Exception $e) {
            $method_map = ['args' => null, 'method' => null, 'caller' => null];
        }

        return $method_map;
    }
}

if ( ! function_exists('resolve_method_map')) {
    /**
     * Resolve Method Map
     *
     * @param array $method_map
     *
     * @return mixed
     */
    function resolve_method_map($method_map)
    {
        if ($method_map['method'] instanceof \ReflectionMethod) {
            return $method_map['method']->invokeArgs($method_map['caller'][2], $method_map['args']);
        } else {
            return call_user_func_array($method_map['caller'], $method_map['args']);
        }
    }
}

if ( ! function_exists('get_http_protocol')) {

    /**
     * Get the HTTP Protocall
     *
     * @return string
     */
    function get_http_protocol()
    {
        return is_ssl() ? 'https' : 'http';
    }
}

if ( ! function_exists('array_reduce_allowed_str')) {

    /**
     * HTML class names helper
     *
     * @return string
     */
    function array_reduce_allowed_str($array) {
        $reduced = '';
        array_walk($array, function($val, $key) use(&$reduced) {
            $reduced .= $val ? " $key" : '';
        });
        $cleaned = implode(' ', array_unique(array_map('trim', explode(' ', trim($reduced)))));
        return $cleaned;
    }
}

if ( ! function_exists('class_names')) {

    /**
     * HTML class names helper
     *
     * @return string
     */
    function class_names($defaults, $classes = null, $failed = '') {
        if(!$result = array_reduce_allowed_str(is_array($defaults) ? $defaults : $classes)) {
            $result = !is_array($classes) ? $classes : $failed;
        }

        $defaults = !is_array($defaults) ? $defaults : '';

        return $defaults . ' ' . $result;
    }
}

if(! function_exists('not_blank_string')) {
    /**
     * Not blank string
     *
     * @param string|null $value
     *
     * @return bool
     */
    function not_blank_string($value) {
        return !(!isset($value) || $value === '');
    }
}

if ( ! function_exists('database')) {
    /**
     * Get WPDB
     *
     * @return \wpdb
     */
    function database() {
        global $wpdb;
        return $wpdb;
    }
}




