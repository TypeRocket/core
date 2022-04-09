<?php
namespace TypeRocket\Elements\Traits;

use TypeRocket\Models\Model;

trait OptionsTrait
{
    protected $options = [];

    /**
     * Set option
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function setOption( $key, $value )
    {
        $this->options[ $key ] = $value;

        return $this;
    }

    /**
     * Set all options
     *
     * @param array|Model $options
     * @param string $style options include standard, flat, flip
     *
     * @return $this
     */
    public function setOptions( $options, $style = 'standard' )
    {
        if($options instanceof Model) {
            return $this->setModelOptions($options);
        }

        switch ($style) {
            case 'key':
                $options = array_combine($options, array_map('\TypeRocket\Utility\Sanitize::underscore', $options));
                break;
            case 'flat':
                $options = array_combine($options, $options);
                break;
            case 'flip' :
                $options = array_flip($options);
                break;
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Get option by key
     *
     * @param string $key
     * @param null|mixed $default
     *
     * @return null
     */
    public function getOption( $key, $default = null )
    {
        return $this->options[ $key ] ?? $default;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get all options and then set the list to
     * an empty array.
     *
     * @return array
     */
    public function popAllOptions()
    {
        $options = $this->options;
        $this->options = [];

        return $options;
    }

    /**
     * @return mixed
     */
    public function popOption()
    {
        return array_pop($this->options);
    }

    /**
     * @return mixed
     */
    public function shiftOption()
    {
        return array_shift($this->options);
    }

    /**
     * Remove option by key
     *
     * @param string $key
     *
     * @return $this
     */
    public function removeOption( $key )
    {
        if ( array_key_exists( $key, $this->options ) ) {
            unset( $this->options[ $key ] );
        }

        return $this;
    }

    /**
     * Set Options from Model
     *
     * @param \TypeRocket\Models\Model|string $model
     * @param string $key_name name of the field column to use as key
     * @param null|string $value_name name of the field column to use as value
     * @param null|string $empty first option with empty value
     *
     * @return $this
     */
    public function setModelOptions($model, $key_name = null, $value_name = null, $empty = null)
    {
        if(is_string($model)) {
            $model = new $model;

            if(method_exists($model, 'limitFieldOptions')) {
                $model->limitFieldOptions();
            }
        }

        $options = $model->findAll()->get() ?? [];

        if(is_string($empty)) {
            $this->options[$empty] = '';
        }

        /** @var Model $option */
        foreach ($options as $option) {
            if(!$value_name) {
                $value_name = $option->getFieldOptions()['value'] ?? $option->getIdColumn();
            }

            if(!$key_name) {
                $key_name = $option->getFieldOptions()['key'] ?? $option->getIdColumn();
            }

            if(method_exists($option, 'getFieldOptionKey')) {
                $key = $option->getFieldOptionKey($key_name);
            } else {
                $key = $option->getDeepValue($key_name);
            }

            if(method_exists($option, 'getFieldOptionValue')) {
                $value = $option->getFieldOptionValue($value_name);
            } else {
                $value = $option->getDeepValue($value_name);
            }

            $this->options[$key] = $value;
        }

        return $this;
    }
}