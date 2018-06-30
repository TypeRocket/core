<?php
namespace TypeRocket\Elements\Fields;

use \TypeRocket\Core\Config;
use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Html\Generator;
use \TypeRocket\Utility\Sanitize;

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
        wp_enqueue_script( 'wp-color-picker'  );
    }

    /**
     * Covert Color to HTML string
     */
    public function getString()
    {
        $name  = $this->getNameAttributeString();
        $value = $this->getValue();
        $default = $this->getDefault();
        $value = !empty($value) ? $value : $default;
        $value = !empty($value) ? Sanitize::hex( $value ) : null;

        $this->removeAttribute( 'name' );
        $this->appendStringToAttribute( 'class', ' color-picker' );
        $palette = 'tr_' . uniqid() . '_color_picker';
        $this->setAttribute('id', $palette);
        $obj = $this;

        $callback = \Closure::bind(function() use ($palette, $obj) {
            wp_localize_script( 'typerocket-scripts', $palette . '_color_palette', $this->getSetting( 'palette' ) );
        }, $this);

        add_action('admin_footer', $callback, 999999999999 );

        if ( tr_is_frontend() && Config::locate('typerocket.frontend.assets') ) {
            add_action('wp_footer', $callback, 999999999999 );
        }

        if ( $this->getSetting( 'palette' ) ) {
            $first_color = $this->getSetting( 'palette' )[0];
            $default_color = !empty($default) ? $default : $first_color;
            $this->setAttribute( 'data-default-color', $default_color );
        }

        $input = new Generator();

        return $input->newInput( 'text', $name, $value, $this->getAttributes() )->getString();
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
