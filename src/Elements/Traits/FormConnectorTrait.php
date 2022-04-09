<?php
namespace TypeRocket\Elements\Traits;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Interfaces\Formable;
use TypeRocket\Models\Model;
use TypeRocket\Utility\DataCollection;
use TypeRocket\Utility\Sanitize;

trait FormConnectorTrait
{
    /** @var mixed|Formable|Model $model */
    protected $model = null;
    protected $itemId = null;
    protected $populate = true;
    protected $group = null;
    protected $sub = null;
    protected $translateLabelDomain = null;
    protected $debugStatus = null;
    protected $prefix = 'tr';

    /**
     * Get Translate Label Domain
     *
     * @return null|string
     */
    public function getLabelTranslationDomain()
    {
        return $this->translateLabelDomain;
    }

    /**
     * Set Translate Label Domain
     *
     * @param string $domain
     * @return $this
     */
    public function setLabelTranslationDomain($domain)
    {
        $this->translateLabelDomain = $domain;

        return $this;
    }

    /**
     * Set Translate Label Domain Shorthand
     *
     * @param null|string $domain
     *
     * @return $this|string|null
     */
    public function domain($domain = null)
    {
        if(func_num_args() === 0) {
             return $this->getLabelTranslationDomain();
        }

        return $this->setLabelTranslationDomain($domain);
    }

    /**
     * Set the form debug status
     *
     * @param bool $status
     *
     * @return $this
     */
    public function setDebugStatus( $status )
    {
        $this->debugStatus = (bool) $status;

        return $this;
    }

    /**
     * Get the From debug status
     *
     * @return bool|null
     */
    public function getDebugStatus()
    {
        return $this->debugStatus === false ? $this->debugStatus : Config::get('app.debug');
    }

    /**
     * Set Model
     *
     * @param array|Formable|Model $model
     *
     * @return $this
     * @throws \ReflectionException
     */
    public function setModel($model)
    {
        if(is_array($model)) {
            $model = new DataCollection($model);
        }
        elseif( is_string($model) && class_exists($model) ) {
            $model = new $model;
        }

        if( $this->itemId && $model instanceof Model && !$model->hasProperties() ) {
            $model = $model->findById($this->itemId);
        }

        if( method_exists($model, 'getRouteResource') && method_exists($this, 'setResource') ) {
            $this->setResource($model->getRouteResource());
        }

        $this->model = $model;

        return $this;
    }

    /**
     * Set Item ID
     *
     * @param $id
     * @return $this
     */
    public function setItemId($id)
    {
        $this->itemId = $id;
        return $this;
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
     * @return mixed|Formable|Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get or Set Group
     *
     * @param null|string $group
     *
     * @return $this|null|string
     */
    public function group($group = null)
    {
        if(func_num_args() === 0) {
            return $this->getGroup();
        }

        $this->setGroup($group);

        return $this;
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
     * @param bool $escape
     *
     * @return $this
     */
    public function appendToGroup($append, $escape = true)
    {
        $append = $escape ? Sanitize::underscore($append, true) : $append;
        $this->group = $this->group ? $this->group . ($append ? '.' . $append : '') : $append;

        return $this;
    }

    /**
     * Prepend To Group
     *
     * @param string $prepend dot notation
     * @param bool $escape
     *
     * @return $this
     */
    public function prependToGroup($prepend, $escape = true)
    {
        $prepend = $escape ? Sanitize::underscore($prepend, true) : $prepend;
        $this->group = $this->group ? ($prepend  ? $prepend . '.' : '') . $this->group : $prepend;

        return $this;
    }

    /**
     * Clone
     *
     * @return $this
     */
    public function clone()
    {
        return clone $this;
    }

    /**
     * Extend Form
     *
     * Only extends by group.
     *
     * @param $group
     *
     * @param bool $escape
     *
     * @return $this
     */
    public function extend($group, $escape = true)
    {
        return $this->clone()->appendToGroup($group, $escape);
    }

    /**
     * Super Extend Form
     *
     * Extends by fields name, group, and subgroup
     *
     * @param string $group
     * @param Field|string $set
     *
     * @return $this
     */
    public function super($group, $set = null)
    {
        $set = $set ?? $this;
        $dots = method_exists($set, 'getDots') ? $set->getDots() : $set;
        return $this->clone()->setGroup($dots . '.' . $group);
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
     * Set Menu Prefix
     *
     * @param $menu_id
     *
     * @return $this
     */
    public function setMenuPrefix($menu_id)
    {
        return $this->setPrefix('tr-menu-'.$menu_id);
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
        return $this->setPrefix('widget-' . $widget->id_base . '[' . $widget->number . ']');
    }
}