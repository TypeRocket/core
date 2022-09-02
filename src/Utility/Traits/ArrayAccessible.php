<?php
namespace TypeRocket\Utility\Traits;

trait ArrayAccessible
{
    /**
     * @var array
     */
    protected array $_items = [];

    /**
     * @param int|string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) : void
    {
        $location = $this->_location ?? '_items';

        if (is_null($offset)) {
            $this->{$location}[] = $value;
        } else {
            $this->{$location}[$offset] = $value;
        }
    }

    /**
     * @param int|string $offset
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        $location = $this->_location ?? '_items';

        return isset($this->{$location}[$offset]);
    }

    /**
     * @param int|string $offset
     * @return void
     */
    public function offsetUnset($offset) : void
    {
        $location = $this->_location ?? '_items';

        unset($this->{$location}[$offset]);
    }

    /**
     * @param int|string $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        $location = $this->_location ?? '_items';

        return $this->{$location}[$offset] ?? null;
    }
}