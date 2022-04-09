<?php
namespace TypeRocket\Core;

use TypeRocket\Exceptions\ResolverException;
use TypeRocket\Interfaces\ResolvesWith;

class Resolver
{
    /**
     * Resolve Class
     *
     * @param string $class
     * @param null|array $args
     *
     * @return object
     * @throws \ReflectionException
     */
    public function resolve($class, $args = null)
    {
        if( $instance = Container::resolve($class)) {
            return $instance;
        }

        $reflector = new \ReflectionClass($class);
        if ( ! $reflector->isInstantiable()) {
            throw new ResolverException($class . ' is not instantiable');
        }
        $constructor = $reflector->getConstructor();
        if ( ! $constructor ) {
            return new $class;
        }
        $parameters   = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters, $args);
        $instance = $reflector->newInstanceArgs($dependencies);

        return $this->onResolution($instance);
    }

    /**
     * On Resolution
     *
     * @param $instance
     * @return mixed
     */
    public function onResolution($instance)
    {
        if($instance instanceof ResolvesWith) {
            $instance = $instance->onResolution();
        }

        return $instance;
    }

    /**
     * Resolve Callable
     *
     * @param $handler
     * @param null|array $args
     * @return mixed
     * @throws \ReflectionException
     */
    public function resolveCallable($handler, $args = null)
    {
        if ( is_array($handler) ) {
            $ref = new \ReflectionMethod($handler[0], $handler[1]);
        } else {
            $ref = new \ReflectionFunction($handler);
        }

        $params = $ref->getParameters();
        $dependencies = $this->getDependencies($params, $args);

        return call_user_func_array( $handler, $dependencies );
    }

    /**
     * Get Dependencies
     *
     * @param array $parameters
     * @param null|array $args
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getDependencies($parameters, $args = null)
    {
        $dependencies = [];
        $i = 0;

        foreach ($parameters as $parameter) {
            $varName = $parameter->getName();
            $dependency = $parameter->getType();

            if (isset($args[$varName])) {
                $v = $args[$varName];
            } elseif (isset($args[$i])) {
                $v = $args[$i];
                $i++;
            } elseif (isset($args['@first'])) {
                $v = $args['@first'];
                unset($args['@first']);
            } else {
                $v = null;
            }

            $isBuiltInType = false;
            if($dependency instanceof \ReflectionNamedType) {
                $isBuiltInType = $dependency->isBuiltin();
            }

            if ( !$dependency || !$dependency instanceof \ReflectionNamedType || $isBuiltInType ) {
                $dependencies[] = $v ?? $this->resolveNonClass($parameter);
            } else {
                $dp_name = $dependency->getName();
                $obj = $v instanceof $dp_name ? $v : $this->resolve($dp_name);

                if($v && method_exists($obj, 'onDependencyInjection')) {
                    $obj->onDependencyInjection($v);
                }

                $dependencies[] = $obj;
            }
        }

        return $dependencies;
    }

    /**
     * Resolve none class
     *
     * Inject default value
     *
     * @param \ReflectionParameter $parameter
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function resolveNonClass(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ResolverException('Resolver failed because there is no default value for the parameter: $' . $parameter->getName());
    }

    /**
     * Resolve Class
     *
     * @param string $class
     * @param null|array $args
     *
     * @return object
     * @throws \ReflectionException
     */
    public static function build($class, $args = null)
    {
        return (new static)->resolve($class, $args);
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }
}