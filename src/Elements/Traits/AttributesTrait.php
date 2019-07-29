<?php

namespace TypeRocket\Elements\Traits;

trait AttributesTrait
{
    public $attr;

    /**
     * Set Attributes
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes( $attributes )
    {
        $this->attr = $attributes;

        return $this;
    }

    /**
     * Get Attribute by key
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attr;
    }

    /**
     * Set Attribute by key
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function setAttribute( $key, $value )
    {
        $this->attr[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param null $default
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
     * @param string $key the attribute if set
     * @param string $text the string to append
     * @param string $separator separate stings by this
     *
     * @return $this
     */
    public function appendStringToAttribute( $key, $text, $separator = ' ' )
    {

        if (array_key_exists( $key, $this->attr )) {
            $text = $this->attr[$key] . $separator . (string) $text;
        }

        $this->attr[$key] = trim($text);

        return $this;
    }

}