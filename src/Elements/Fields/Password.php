<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\BeforeAfterSetting;
use TypeRocket\Elements\Traits\GlobalTextFieldAttributes;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;

class Password extends Field
{
    use RequiredTrait, BeforeAfterSetting, GlobalTextFieldAttributes;

    protected $labelTag = 'label';
    protected $populate = false;

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->setType( 'password' );
    }

    /**
     * Covert Test to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $this->setupInputId();
        $this->setAttribute('data-tr-field', $this->getContextId());
        $name = $this->getNameAttributeString();
        $value = $this->setCast('string')->getValue();
        $value = !empty($value) || $value == '0' ? $value : '';
        $value = $this->sanitize($value, 'raw');

        if($before = $this->getBefore()) {
            $this->attrClass('with-before');
            $before = '<div class="before"><span>' . $before . '</span></div>';
        }

        if($after = $this->getAfter()) {
            $this->attrClass('with-after');
            $after = '<div class="after"><span>' . $after . '</span></div>';
        }

        return '<div class="tr-text-input">' . $before . Html::input($this->getType(), $name, $value, $this->getAttributes()) . $after . '</div>';
    }
}