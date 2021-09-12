<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Html\Html;

class Submit extends Field
{

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'submit' );
    }

    /**
     * Covert Submit to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name = '_tr_submit_form';
        $value = esc_attr( $this->getAttribute('value') );
        $this->removeAttribute('value');
        $this->removeAttribute('name');
        $this->attrClass('button button-primary');

        return (string) Html::input('submit', $name, $value, $this->getAttributes());
    }

}