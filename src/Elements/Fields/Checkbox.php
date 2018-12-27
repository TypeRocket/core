<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Html\Generator;

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
        $name   = $this->getNameAttributeString();
        $this->removeAttribute( 'name' );
        $default = $this->getDefault();
        $option = $this->getValue();
        $checkbox = new Generator();
        $field = new Generator();
        $this->setAttribute( 'value', '1' );

        if ($option == '1' || ! is_null($option) && $option == $this->getAttribute('value')) {
            $this->setAttribute( 'checked', 'checked' );
        } elseif($default === true && is_null($option)) {
            $this->setAttribute( 'checked', 'checked' );
        }

        $checkbox->newInput( 'checkbox', $name, '1', $this->getAttributes() );

        $field->newElement( 'label' )
            ->appendInside( $checkbox )
            ->appendInside( 'span', [], $this->getSetting( 'text' ) );

        if ($default !== false) {
            $hidden = new Generator();
            $field->prependInside( $hidden->newInput('hidden', $name, '0' ) );
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