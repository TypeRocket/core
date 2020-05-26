<?php

namespace TypeRocket\Utility;

class Optional
{
    /**
     * @var object
     */
    private $object;

    /**
     * Optional constructor
     *
     * @param object $object
     */
    public function __construct( $object )
    {
        $this->object = $object;
    }

    /**
     * Gracefully get the object property or return null for non-objects
     *
     * @param  string $name
     * @return mixed|null
     */
    public function __get( $name )
    {
        if ( ! empty( $this->object ) && ! empty( $this->object->{$name} ) ) {
            return $this->object->{$name};
        }

        return null;
    }

    /**
     * Gracefully call the object method or return null for non-objects
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed|null
     */
    public function __call( $name, $arguments )
    {
        if ( ! empty( $this->object ) && method_exists( $this->object, $name ) ) {
           return call_user_func_array( array( $this->object, $name ), $arguments );
        }

        return null;
    }
}
