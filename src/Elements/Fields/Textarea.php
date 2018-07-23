<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Html\Generator;
use \TypeRocket\Elements\Traits\MaxlengthTrait;

class Textarea extends Field
{
    use MaxlengthTrait, DefaultSetting;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'textarea' );
    }

    /**
     * Covert Textarea to HTML string
     */
    public function getString()
    {
        $generator = new Generator();
        $this->setAttribute('name', $this->getNameAttributeString());
        $value = $this->getValue();
        $default = $this->getDefault();
        $this->setupInputId();
        $value = !empty($value) ? $value : $default;
        $value = $this->sanitize($value, 'textarea');
        $max = $this->getMaxlength( $value,  $this->getAttribute('maxlength'));

        return $generator->newElement( 'textarea', $this->getAttributes(), $value )->getString() . $max;
    }

}