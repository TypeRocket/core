<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Html\Html;

class Toggle extends Field
{
    use DefaultSetting;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType('toggle');
    }

    /**
     * Covert Checkbox to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name = $this->getNameAttributeString();
        $this->removeAttribute( 'name' );
        $default = $this->getDefault();
        $option = $this->getValue();
        $this->setAttribute('data-tr-field', $this->getContextId());
        $this->setupInputId();

        if ($option == '1' || ! is_null($option) && (!empty($option) && $option == $this->getAttribute('value')) ) {
            $this->setAttribute( 'checked', 'checked' );
        } elseif($default === true && is_null($option)) {
            $this->setAttribute( 'checked', 'checked' );
        }

        $field = Html::div(['class' => 'tr-toggle-box'])
              ->nest( Html::input('checkbox', $name, '1', $this->getAttributes() ) )
              ->nest( Html::label([
                  'tabindex' => '0',
                  'for' => $this->getInputId(),
                  'class' => 'tr-toggle-box-label'
              ]));

        if($text = $this->getSetting( 'text' )) {
            $field->nest(Html::p( ['class' => 'tr-toggle-box-text'], $text));
        }

        if ($default !== false) {
            $field->nestAtTop( Html::input('hidden', $name, '0' ) );
        }

        return $field->getString();
    }

    /**
     * Add text description next to checkbox
     *
     * @param string $text
     *
     * @return $this
     */
    public function setText( $text = '' ) {
        $this->setSetting('text', $text);

        return $this;
    }
}