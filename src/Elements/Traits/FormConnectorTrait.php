<?php

namespace TypeRocket\Elements\Traits;

use TypeRocket\Utility\Sanitize;

trait FormConnectorTrait
{

    protected $resource = null;
    protected $action = null;
    protected $itemId = null;

    /** @var \TypeRocket\Models\Model $model */
    protected $model = null;

    protected $populate = true;
    protected $group = null;
    protected $sub = null;
    protected $settings = [];
    protected $prefix = 'tr';

    /**
     * Get controller
     *
     * @return null|string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set Action
     *
     * @return null|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get Item ID
     *
     * @return null|string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Get Model
     *
     * @return \TypeRocket\Models\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set Group into dot notation
     *
     * @param string $group
     *
     * @return $this
     */
    public function setGroup( $group )
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get Group
     *
     * @return null|string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Append To Group
     *
     * @param string $append dot notation
     * @return $this
     */
    public function appendToGroup($append)
    {
        $append = Sanitize::underscore($append, true);
        $this->group = $this->group ? $this->group . '.' . $append : $append;

        return $this;
    }

    /**
     * Prepend To Group
     *
     * @param string $prepend dot notation
     * @return $this
     */
    public function prependToGroup($prepend)
    {
        $this->group = $prepend . '.' . $this->group;

        return $this;
    }

    /**
     * Set whether to populate Field from database. If set to false fields will
     * always be left empty and with their default values.
     *
     * @param bool $populate
     *
     * @return $this
     */
    public function setPopulate( $populate )
    {
        $this->populate = (bool) $populate;

        return $this;
    }

    /**
     * Get populate
     *
     * @return bool
     */
    public function getPopulate()
    {
        return $this->populate;
    }

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
     * @param string $value
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
     * Get From setting by key
     *
     * @param string $key
     * @param null $default default value to return if none
     *
     * @return null
     */
    public function getSetting( $key, $default = null )
    {
        if ( ! array_key_exists( $key, $this->settings )) {
            return $default;
        }

        return $this->settings[$key];
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

    /**
     * Render Setting
     *
     * By setting render to 'raw' the form will not add any special html wrappers.
     * You have more control of the design when render is set to raw.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRenderSetting( $value )
    {
        $this->settings['render'] = $value;

        return $this;
    }

    /**
     * Get render mode
     *
     * @return null
     */
    public function getRenderSetting()
    {
        if ( ! array_key_exists( 'render', $this->settings )) {
            return null;
        }

        return $this->settings['render'];
    }

    /**
     * Get Name Attribute Prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set Name Attribute Prefix
     *
     * Use this mainly for Widgets. Should be used with caution
     * as the default value `tr` is required for some fields
     * and features to work.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setPrefix($name)
    {
        $this->prefix = $name;
        return $this;
    }

    /**
     * Set Widget Name Attribute Prefix
     *
     * @param \WP_Widget $widget
     *
     * @return $this
     */
    public function setWidgetPrefix(\WP_Widget $widget)
    {
        $this->setPrefix('widget-' . $widget->id_base . '[' . $widget->number . ']');
        return $this;
    }
}