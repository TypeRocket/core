<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Html\Generator;
use \TypeRocket\Elements\Traits\MaxlengthTrait;

class Text extends Field
{
    use MaxlengthTrait, DefaultSetting;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'text' );
    }

    /**
     * Covert Test to HTML string
     */
    public function getString()
    {
        $input = new Generator();
        $name = $this->getNameAttributeString();
        $value = $this->getValue();
        $default = $this->getDefault();
        $this->setupInputId();

        $value = !empty($value) || $value == '0' ? $value : $default;
        $value = $this->sanitize($value, 'raw');
        $max = $this->getMaxlength( $value, $this->getAttribute('maxlength'));

        return $input->newInput($this->getType(), $name, esc_attr($value), $this->getAttributes() )->getString() . $max;
    }

}