<?php

namespace TypeRocket\Core;


class Resolver
{

    /**
     * Resolve Class
     *
     * @param string $class
     *
     * @return object
     * @throws \Exception
     */
    public function resolve($class)
    {
        if( $instance = Injector::resolve($class)) {
            return $instance;
        }

        $reflector = new \ReflectionClass($class);
        if ( ! $reflector->isInstantiable()) {
            throw new \Exception($class . ' is not instantiable');
        }
        $constructor = $reflector->getConstructor();
        if ( ! $constructor ) {
            return new $class;
        }
        $parameters   = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters);
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Get Dependencies
     *
     * @param string $parameters
     *
     * @return array
     * @throws \Exception
     */
    public function getDependencies($parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            if ( ! $dependency ) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolve($dependency->name);
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
     * @throws \Exception
     */
    public function resolveNonClass(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        throw new \Exception('Cannot resolve using reflection');
    }
}