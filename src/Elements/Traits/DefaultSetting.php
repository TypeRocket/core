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

    /**
     * Maybe Set Default
     *
     * @param mixed|null $value value to set if none exists
     *
     * @return null
     */
    public function maybeSetDefault( $value = null )
    {
        if ( ! $this->getDefault() ) {
            $this->setSetting('default', $value);
        }

        return $this;
    }
}