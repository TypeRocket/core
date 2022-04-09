<?php
namespace TypeRocket\Elements\Traits;

trait Attributes
{
    protected $attr = [];

    /**
     * Attribute Shorthand
     *
     * @param null|string|array $key
     * @param null|mixed $value
     *
     * @return $this|array|string|bool|int|float
     */
    public function attr($key = null, $value = null)
    {
        $num = func_num_args();

        if($num == 0) {
            return $this->getAttributes();
        }

        if(is_array($key)) {
            return $this->attrExtend($key);
        }

        if($num == 1) {
            return $this->getAttribute($key);
        }

        return $this->setAttribute($key, $value);
    }

    /**
     * Set Attributes
     *
     * @param array|null $attributes
     *
     * @return $this
     */
    public function attrReset( array $attributes = [] )
    {
        $this->attr = $attributes;

        return $this;
    }

    /**
     * Extend Attributes
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function attrExtend( array $attributes )
    {
        $this->attr = array_merge($this->attr, $attributes);

        return $this;
    }

    /**
     * Get Attribute by key
     *
     * @param null|array $with
     *
     * @return array
     */
    public function getAttributes($with = null)
    {
        return !$with ? $this->attr : array_merge($this->attr, $with);
    }

    /**
     * Set Attribute by key
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function setAttribute( $key, $value = '' )
    {
        if(!is_null($value)) {
            $this->attr[$key] = $value;
        }

        return $this;
    }

    /**
     * @param string $key
     * @param null|mixed $default
     *
     * @return null
     */
    public function getAttribute( $key, $default = null )
    {
        if ( ! array_key_exists( $key, $this->attr )) {
            return $default;
        }

        return $this->attr[$key];
    }

    /**
     * Get all options and then set the list to
     * an empty array.
     *
     * @return array
     */
    public function popAllAttributes()
    {
        $options = $this->attr;
        $this->attr = [];

        return $options;
    }

    /**
     * @return mixed
     */
    public function popAttribute()
    {
        return array_pop($this->attr);
    }

    /**
     * @return mixed
     */
    public function shiftAttribute()
    {
        return array_shift($this->attr);
    }

    /**
     * Remove Attribute by key
     *
     * @param string $key
     *
     * @return $this
     */
    public function removeAttribute( $key )
    {

        if (array_key_exists( $key, $this->attr )) {
            unset( $this->attr[$key] );
        }

        return $this;
    }

    /**
     * Append a string to an attribute
     *
     * @param string $value the string to append
     *
     * @return $this|string|null
     */
    public function attrClass($value)
    {
        $num = func_num_args();

        if($num == 0) {
            return $this->attr['class'] ?? null;
        }

        $this->attr['class'] = ($this->attr['class'] ?? '' ) . ' ' . $value;

        return $this;
    }

    /**
     * @param bool $bool
     * @param string $value
     *
     * @return $this
     */
    public function attrClassIf($bool, $value)
    {
        if($bool) {
            $this->attrClass($value);
        }

        return $this;
    }

    /**
     * Maybe Set Attribute
     *
     * @param string $key
     * @param mixed|null $value value to set if none exists
     *
     * @return null
     */
    public function maybeSetAttribute( $key, $value = null )
    {
        if ( ! $this->getAttribute($key) ) {
            $this->attr[$key] = $value;
        }

        return $this;
    }

}