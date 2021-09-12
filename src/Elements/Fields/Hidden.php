<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Html\Html;

class Hidden extends Field
{
    use DefaultSetting;

    protected $rawHtml = true;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType('hidden');
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
        $default = $this->getDefault();

        $value = !empty($value) || $value == '0' ? $value : $default;
        $value = $this->sanitize($value, 'raw');

        return (string) Html::input('hidden', $name, $value, $this->getAttributes());
    }
}