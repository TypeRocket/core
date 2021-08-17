<?php
namespace TypeRocket\Elements\Traits;

trait GlobalTextFieldAttributes
{
    /**
     * Set Placeholder
     *
     * @param string $text
     *
     * @return $this
     */
    public function setPlaceholder($text)
    {
        return $this->setAttribute('placeholder', $text);
    }

    /**
     * Get Placeholder
     *
     * @return string
     */
    public function getPlaceholder()
    {
        return (string) $this->getAttribute('placeholder');
    }
}