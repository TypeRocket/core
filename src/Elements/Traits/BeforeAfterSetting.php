<?php
namespace TypeRocket\Elements\Traits;

use TypeRocket\Elements\Dashicons;

trait BeforeAfterSetting
{
    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setBeforeIcon($icon)
    {
        return $this->setBefore(Dashicons::getIconHtml($icon));
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setAfterIcon($icon)
    {
        return $this->setAfter(Dashicons::getIconHtml($icon));
    }

    /**
     * @param string|null $string
     *
     * @return $this
     */
    public function setBefore($string)
    {
        return $this->setSetting('before', $string);
    }

    /**
     * @param string|null $string
     *
     * @return $this
     */
    public function setAfter($string)
    {
        return $this->setSetting('after', $string);
    }

    /**
     * @return string|null
     */
    public function getAfter()
    {
        return $this->getSetting('after');
    }

    /**
     * @return string|null
     */
    public function getBefore()
    {
        return $this->getSetting('before');
    }
}