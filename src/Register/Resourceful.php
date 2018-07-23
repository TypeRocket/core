<?php

namespace TypeRocket\Register;

use TypeRocket\Utility\Inflect;
use TypeRocket\Utility\Sanitize;

trait Resourceful
{
    /**
     * Set the Registrable ID for WordPress to use. Don't use reserved names.
     *
     * @param string $id set the ID
     * @param boolean $resource update the resource binding
     *
     * @return $this
     */
    public function setId($id, $resource = false)
    {
        $this->id = Sanitize::underscore($id);
        $this->dieIfReserved();

        if($resource) {
            $singular     = Sanitize::underscore( $this->id );
            $plural       = Sanitize::underscore( Inflect::pluralize($this->id) );
            $this->resource = [$singular, $plural];
        }

        return $this;
    }
    
    /**
     * Set the Registrable ID for WordPress to use. Don't use reserved names.
     *
     * @param string $id set the ID in raw form
     * @param boolean $resource update the resource binding
     *
     * @return $this
     */
    public function setRawId($id, $resource = false)
    {
        $this->id = $id;
        $this->dieIfReserved();

        if($resource) {
            $singular     = $this->id;
            $plural       = Inflect::pluralize($this->id);
            $this->resource = [$singular, $plural];
        }

        return $this;
    }
}
