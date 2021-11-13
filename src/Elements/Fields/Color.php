<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Core\System;
use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Html\Html;
use TypeRocket\Utility\Sanitize;

class Color extends Field implements ScriptField
{
    use DefaultSetting;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'color' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts() {
        wp_enqueue_script( 'wp-color-picker' );
    }

    /**
     * Covert Color to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name  = $this->getNameAttributeString();
        $value = $this->setCast('string')->getValue();
        $default = $this->getDefault();
        $value = !empty($value) ? $value : $default;
        $value = !empty($value) ? Sanitize::hex( $value ) : null;

        $this->removeAttribute( 'name' );
        $this->attrClass('tr-color-picker');
        $palette = 'tr_color_palette_' . uniqid();
        $this->setAttribute('data-palette', $palette);
        $this->setAttribute('data-tr-field', $this->getContextId());

        $callback = function() use ($palette) {
            if($colors = $this->getSetting( 'palette' )) {
                wp_localize_script( 'typerocket-scripts', $palette, $colors );
            }
        };

        if ( !is_admin() && System::getFromContainer()->frontendIsEnabled() ) {
            add_action('wp_footer', $callback, 999999999999 );
        } else {
            add_action('admin_footer', $callback, 999999999999 );
        }

        if ( $this->getSetting( 'palette' ) ) {
            $first_color = $this->getSetting( 'palette' )[0];
            $default_color = !empty($default) ? $default : $first_color;
            $this->setAttribute( 'data-default-color', $default_color );
        }


        return (string) Html::input( 'text', $name, $value, $this->getAttributes() );
    }

    /**
     * Set color palette
     *
     * Use 6 character hex only eg. [ '#222222', '#000000' ]
     *
     * @param array $palette set the color palette
     *
     * @return $this
     */
    public function setPalette( $palette ) {
        if( ! empty( $palette) ) {
            $this->setSetting('palette', $palette );
        }

        return $this;
    }

}
