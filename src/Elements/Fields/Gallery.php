<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\ImageFeaturesTrait;
use TypeRocket\Html\Html;

class Gallery extends Field implements ScriptField
{
    use ImageFeaturesTrait;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'gallery' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts() {
        wp_enqueue_media();
    }

    /**
     * Covert Image to HTML string
     */
    public function getString()
    {
        $name = $this->getNameAttributeString();
        $this->attrClass( 'image-picker' );
        $images = $this->getValue();
        $this->removeAttribute('name');

        if (! $this->getSetting( 'button' )) {
            $this->setSetting('button', __('Insert Images', 'typerocket-domain'));
        }

        if ( ! $this->getSetting( 'clear' )) {
            $this->setSetting( 'clear', __('Clear', 'typerocket-domain') );
        }

        $list = '';

        if (is_array( $images )) {
            foreach ($images as $id) {
                $input = (string) Html::input( 'hidden', $name . '[]', $id );
                $image = wp_get_attachment_image( (int) $id, 'thumbnail' );
                $remove = '#remove';

                if ( ! empty( $image )) {
                    $list .= Html::el( 'li',
                        [
                            'tabindex' => '0',
                            'class' => 'tr-gallery-item tr-image-picker-placeholder'
                        ],
                        '<a tabindex="0" class="dashicons dashicons-no-alt tr-gallery-remove" title="'.__('Remove Image', 'typerocket-domain').'" href="'.$remove.'"></a>' . $image . $input
                    );
                }

            }
        }

        $this->removeAttribute('id');

        $html = (string) Html::input( 'hidden', $name, '0', $this->getAttributes() );
        $html .= '<div class="button-group">';
        $html .= Html::el( 'input', [
            'type'  => 'button',
            'class' => 'tr-gallery-picker-button button',
            'value' => $this->getSetting( 'button' )
        ]);
        $html .= Html::el( 'input', [
            'type'  => 'button',
            'class' => 'tr-gallery-picker-clear button',
            'value' => $this->getSetting( 'clear' )
        ]);
        $html .= '</div>';
        $html .= Html::el( 'ul', [
            'class' => 'tr-gallery-list cf'
        ], $list );

        return $html;
    }

}
