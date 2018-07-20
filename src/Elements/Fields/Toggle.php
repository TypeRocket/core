<?php

namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Html\Generator;

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
        $name   = $this->getNameAttributeString();
        $this->removeAttribute( 'name' );
        $default = $this->getDefault();
        $option = $this->getValue();
        $this->setupInputId();
        $checkbox = new Generator();
        $field = new Generator();

        if ($option == '1' || ! is_null($option) && $option == $this->getAttribute('value')) {
            $this->setAttribute( 'checked', 'checked' );
        } elseif($default === true && is_null($option)) {
            $this->setAttribute( 'checked', 'checked' );
        }

        $checkbox->newInput('checkbox', $name, '1', $this->getAttributes() );

        $field->newElement('div', ['class' => 'tr-toggle-box'])
              ->appendInside( $checkbox )
              ->appendInside( 'label', [
                  'tabindex' => '0',
                  'for' => $this->getInputId(),
                  'data-trfor' => $this->getAttribute('data-trid'),
                  'class' => 'tr-toggle-box-label'
              ]);

        if($text = $this->getSetting( 'text' )) {
            $field->appendInside('p', ['class' => 'tr-toggle-box-text'], $text);
        }

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