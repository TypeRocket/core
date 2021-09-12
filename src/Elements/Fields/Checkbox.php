<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Html\Html;

class Checkbox extends Field
{
    use DefaultSetting;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'checkbox' );
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
        $checkbox = new Html();
        $field = new Html();
        $this->setAttribute( 'value', '1' );
        $this->setAttribute('data-tr-field', $this->getContextId());

        if ($option == '1' || ! is_null($option) && (!empty($option) && $option == $this->getAttribute('value'))) {
            $this->setAttribute( 'checked', 'checked' );
        } elseif($default === true && is_null($option)) {
            $this->setAttribute( 'checked', 'checked' );
        }

        $checkbox->input( 'checkbox', $name, '1', $this->getAttributes() );

        $field->el( 'label' )
            ->nest( [$checkbox, Html::span($this->getSetting( 'text' ))] );

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