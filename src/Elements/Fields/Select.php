<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Elements\Traits\OptionsTrait;
use \TypeRocket\Html\Generator;

class Select extends Field
{

    use OptionsTrait, DefaultSetting;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'select' );
    }

    /**
     * Covert Select to HTML string
     */
    public function getString()
    {
        $default = $this->getSetting('default');
        $name = $this->getNameAttributeString();
        $this->setupInputId();
        if( $this->getAttribute('multiple') ) {
            $name = $name . '[]';
        }

        $this->setAttribute('name', $name);
        $option = $this->getValue();
        $option = ! is_null($option) ? $option : $default;

        $generator  = new Generator();
        $generator->newElement( 'select', $this->getAttributes() );

        foreach ($this->options as $key => $value) {

            if( is_array($value) ) {

                $optgroup  = new Generator();
                $optgroup->newElement( 'optgroup', ['label' => $key] );

                foreach($value as $k => $v) {
                    $attr['value'] = $v;

                    if(is_array($option) && in_array($v, $option)) {
                        $attr['selected'] = 'selected';
                    } elseif ( !is_array($option) && $option == $v && isset($option) ) {
                        $attr['selected'] = 'selected';
                    } else {
                        unset( $attr['selected'] );
                    }

                    $optgroup->appendInside( 'option', $attr, (string) $k );
                }

                $generator->appendInside( $optgroup );

            } else {
                $attr['value'] = $value;
                if(is_array($option) && in_array($value, $option)) {
                    $attr['selected'] = 'selected';
                } elseif ( !is_array($option) && $option == $value && isset($option) ) {
                    $attr['selected'] = 'selected';
                } else {
                    unset( $attr['selected'] );
                }

                $generator->appendInside( 'option', $attr, (string) $key );
            }

        }

        return $generator->getString();
    }

    /**
     * Make select multiple
     *
     * @return $this
     */
    public function multiple()
    {
        return $this->setAttribute('multiple', 'multiple');
    }
}