<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\GlobalTextFieldAttributes;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;
use TypeRocket\Elements\Traits\MaxlengthTrait;

class Textarea extends Field
{
    use MaxlengthTrait, DefaultSetting, RequiredTrait, GlobalTextFieldAttributes;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType('textarea');
    }

    /**
     * Covert Textarea to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $this->setupInputId();
        $this->setAttribute('data-tr-field', $this->getContextId());
        $this->setAttribute('name', $this->getNameAttributeString());
        $value = $this->setCast('string')->getValue();
        $default = $this->getDefault();

        $value = !empty($value) ? $value : $default;
        $value = $this->sanitize($value, 'raw');
        $max = $this->getMaxlength($value, $this->getAttribute('maxlength'));

        if($max) {
            $this->attrClass('tr-input-maxlength');
        }

        return (string) Html::textarea($this->getAttributes(), $value) . $max;
    }

}