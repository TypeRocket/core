<?php

namespace TypeRocket\Utility\Traits;

trait ArrayIterable
{
    /**
     * @var array
     */
    protected array $_items = [];

    function rewind()
    {
        $location = $this->_location ?? '_items';

        reset($this->{$location});
    }

    function current()
    {
        $location = $this->_location ?? '_items';

        return current($this->{$location});
    }

    function key()
    {
        $location = $this->_location ?? '_items';

        return key($this->{$location});
    }

    function next()
    {
        $location = $this->_location ?? '_items';

        next($this->{$location});
    }

    function valid()
    {
        $location = $this->_location ?? '_items';

        return key($this->{$location}) !== null;
    }
}