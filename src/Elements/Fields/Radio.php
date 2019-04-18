<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Elements\Traits\OptionsTrait;
use \TypeRocket\Html\Generator;

class Radio extends Field
{

    use OptionsTrait, DefaultSetting;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'radio' );
    }

    /**
     * Covert Radio to HTML string
     */
    public function getString()
    {
        $name    = $this->getNameAttributeString();
        $default = $this->getSetting('default');
        $mode = $this->getSetting('mode');
        $ul_classes = $this->getSetting('ul_classes');
        $option  = $this->getValue();
        $option  = ! is_null($option) ? $option : $default;
        $this->removeAttribute('name');
        $id = $this->getAttribute('id', '');
        $this->removeAttribute('id');
        $generator = new Generator();

        if($id) { $id = "id=\"{$id}\""; }

        $classes = $mode == 'image' ? 'radio-images' : 'data-full';
        $classes = $ul_classes ? $ul_classes . ' ' . $classes : $classes;

        $field = "<ul class=\"{$classes}\" {$id}>";

        foreach ($this->options as $key => $value) {
            $content = $key;

            if($mode == 'image') {
                $src = $value['src'];
                $value = $value['value']; // keep as last setter

                $content =  "<img src='{$src}' class='radio-images-image' alt='{$key}' />";
            }

            if ( $option == $value && isset($option) ) {
                $this->setAttribute('checked', 'checked');
            } else {
                $this->removeAttribute('checked');
            }

            $field .= "<li><label>";
            $field .= $generator->newInput( 'radio', $name, $value, $this->getAttributes() )->getString();
            $field .= "<span>{$content}</span></label></li>";
        }

        $field .= '</ul>';

        return $field;
    }

    /**
     * Use images instead of text
     *
     * @param string $ul_classes 'default'
     * @return $this
     */
    public function useImages($ul_classes = 'tr-flex-tight tr-round-image-corners')
    {
        $this->settings['mode'] = 'image';
        $this->settings['ul_classes'] = $ul_classes;

        return $this;
    }

}