<?php

namespace TypeRocket\Utility\Traits;

trait ArrayIterable
{
    /**
     * @var array
     */
    protected array $_items = [];

    #[\ReturnTypeWillChange]
    function rewind()
    {
        $location = $this->_location ?? '_items';

        reset($this->{$location});
    }

    #[\ReturnTypeWillChange]
    function current()
    {
        $location = $this->_location ?? '_items';

        return current($this->{$location});
    }

    #[\ReturnTypeWillChange]
    function key()
    {
        $location = $this->_location ?? '_items';

        return key($this->{$location});
    }

    #[\ReturnTypeWillChange]
    function next()
    {
        $location = $this->_location ?? '_items';

        next($this->{$location});
    }

    #[\ReturnTypeWillChange]
    function valid()
    {
        $location = $this->_location ?? '_items';

        return key($this->{$location}) !== null;
    }
}