<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\BeforeAfterSetting;
use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\GlobalTextFieldAttributes;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;
use TypeRocket\Elements\Traits\MaxlengthTrait;

class Text extends Field
{
    use MaxlengthTrait, DefaultSetting, RequiredTrait, BeforeAfterSetting, GlobalTextFieldAttributes;

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
        if(!$this->canDisplay()) { return ''; }

        $this->setupInputId();
        $this->setCast('string');
        $this->setAttribute('data-tr-field', $this->getContextId());
        $name = $this->getNameAttributeString();
        $value = $this->getValue();
        $default = $this->getDefault();
        $value = !empty($value) || $value == '0' ? $value : $default;
        $value = $this->sanitize($value, 'raw');
        $max = $this->getMaxlength( $value, $this->getAttribute('maxlength'));

        if($max) {
            $this->attrClass('tr-input-maxlength');
        }

        if($before = $this->getBefore()) {
            $this->attrClass('with-before');
            $before = '<div class="before"><span>' . $before . '</span></div>';
        }

        if($after = $this->getAfter()) {
            $this->attrClass('with-after');
            $after = '<div class="after"><span>' . $after . '</span></div>';
        }

        return '<div class="tr-text-input">' . $before . Html::input($this->getType(), $name, $value, $this->getAttributes()) . $after . '</div>' . $max;
    }

    /**
     * Spellcheck
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/email#spellcheck
     *
     * @param bool $use
     * @return Text
     */
    public function spellcheck($use = true)
    {
        return $this->setAttribute('spellcheck', $use ? 'true' : 'false');
    }

}