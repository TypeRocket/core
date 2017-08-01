<?php

namespace TypeRocket\Elements\Traits;

trait DefaultSetting
{
    /**
     * Set default value
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setDefault( $value = '' ) {
        $this->setSetting('default', $value);

        return $this;
    }

    /**
     * Set default value
     *
     * @return $this
     */
    public function getDefault() {
        return $this->getSetting('default');
    }
}