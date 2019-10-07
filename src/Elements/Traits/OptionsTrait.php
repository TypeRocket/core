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
     * @param array $options
     * @param string $style options include standard, flat, flip
     *
     * @return $this
     */
    public function setOptions( $options, $style = 'standard' )
    {

        switch ($style) {
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
     * @param null $default
     *
     * @return null
     */
    public function getOption( $key, $default = null )
    {
        if ( ! array_key_exists( $key, $this->options ) ) {
            return $default;
        }

        return $this->options[ $key ];
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
     * @param \TypeRocket\Models\Model $model
     * @param string $key_name name of the field column to use as key
     * @param null|string $value_name name of the field column to use as value
     *
     * @return $this
     * @throws \Exception
     */
    public function setModelOptions(Model $model, $key_name, $value_name = null)
    {
        $options = clone $model->findAll()->get();

        /** @var Model $option */
        foreach ($options as $option) {
            if(!$value_name) {
                $value_name = $model->getIdColumn();
            }

            $key = $option->getDeepValue($key_name);
            $value = $option->getDeepValue($value_name);

            $this->options[$key] = $value;
        }

        return $this;
    }
}