<?php
namespace TypeRocket\Elements\Traits;

trait Settings
{
    protected $settings = [];

    /**
     * Set From settings
     *
     * @param array $settings
     *
     * @return $this
     */
    public function setSettings( $settings )
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Set settings
     *
     * @param array|null $settings
     *
     * @return $this
     */
    public function settingsReset( array $settings = [] )
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Extend settings
     *
     * @param array $settings
     *
     * @return $this
     */
    public function settingsExtend( array $settings )
    {
        $this->settings = array_merge($this->settings, $settings);

        return $this;
    }

    /**
     * Get Form settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set Form setting by key
     *
     * @param string $key
     * @param string|array|object $value
     *
     * @return $this
     */
    public function setSetting( $key, $value )
    {
        $this->settings[$key] = $value;

        return $this;
    }

    /**
     * Append to Array Setting
     *
     * Append to an array setting
     *
     * @param string $setting
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function appendToArraySetting( $setting, $key, $value )
    {
        $this->settings[$setting][$key] = $value;

        return $this;
    }

    /**
     * Append to String Setting
     *
     * @param string $setting
     * @param string $value
     * @param string $spacer
     *
     * @return $this
     */
    public function appendToStringSetting($setting, string $value, $spacer = ' ')
    {
        $this->settings[$setting] = ($this->settings[$setting] ?? '' ) . $spacer . $value;

        return $this;
    }

    /**
     * Get From setting by key
     *
     * @param string $key
     * @param null|mixed $default default value to return if none
     *
     * @return null
     */
    public function getSetting( $key, $default = null )
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Maybe Set Setting
     *
     * @param string $key
     * @param mixed|null $value value to set if none exists
     *
     * @return null
     */
    public function maybeSetSetting( $key, $value = null )
    {
        if ( ! $this->getSetting($key) ) {
            $this->settings[$key] = $value;
        }

        return $this;
    }

    /**
     * Remove setting bby key
     *
     * @param string $key
     *
     * @return $this
     */
    public function removeSetting( $key )
    {
        if (array_key_exists( $key, $this->settings )) {
            unset( $this->settings[$key] );
        }

        return $this;
    }
}