<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Html\Html;

class File extends Field implements ScriptField
{
    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'file' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts() {
        wp_enqueue_media();
    }

    /**
     * Covert File to HTML string
     */
    function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name = $this->getNameAttributeString();
        $this->attrClass('file-picker');
        $value = $this->setCast('int')->getValue();
        $value = (int) $value !== 0 ? $value : null;
        $this->removeAttribute( 'name' );
        $this->setAttribute('data-tr-field', $this->getContextId());
        $generator = new Html();

        if ( ! $this->getSetting( 'button' )) {
            $this->setSetting( 'button', __('Insert File') );
        }

        if ( ! $this->getSetting( 'clear' )) {
            $this->setSetting( 'clear', __('Clear') );
        }

        if ($value != "") {
            $url  = wp_get_attachment_url( $value );
            $file = '<a target="_blank" href="' . $url . '">' . $url . '</a>';
        } else {
            $file = '';
        }

        $html = $generator->input( 'hidden', $name, $value, $this->getAttributes() )->getString();
        $html .= '<div class="button-group">';
        $html .= $generator->el( 'input', [
            'type'  => 'button',
            'class' => 'tr-file-picker-button button',
            'value' => $this->getSetting( 'button' ),
            'data-type' => $this->getSetting( 'type', '' ) // https://codex.wordpress.org/Function_Reference/get_allowed_mime_types
        ]);
        $html .= $generator->el( 'input', [
            'type'  => 'button',
            'class' => 'tr-file-picker-clear button',
            'value' => $this->getSetting( 'clear' )
        ]);
        $html .= '</div>';
        $html .= $generator->div([
            'class' => 'tr-file-picker-placeholder'
        ], $file );

        return $html;
    }

    /**
     * Set Mime Type
     *
     * @param $type
     *
     * @return File
     */
    public function setMimeType($type)
    {
        return $this->setSetting('type', $type);
    }

}