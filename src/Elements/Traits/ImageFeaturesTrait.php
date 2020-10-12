<?php
namespace TypeRocket\Elements\Traits;

trait ImageFeaturesTrait
{
    /**
     * Set Admin Image Size
     *
     * @param string $size
     *
     * @return $this
     */
    public function setAdminImageSize($size = 'thumbnail')
    {
        return $this->setSetting('size', $size);
    }

    /**
     * Set Background Dark
     *
     * @return $this
     */
    public function setBackgroundDark()
    {
        return $this->setSetting('background', 'dark');
    }
}