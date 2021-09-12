<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\OptionsTrait;
use TypeRocket\Html\Html;

class Radio extends Field
{

    use OptionsTrait, DefaultSetting;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType('radio');
    }

    /**
     * Covert Radio to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name    = $this->getNameAttributeString();
        $default = $this->getSetting('default');
        $mode = $this->getSetting('mode');
        $ul_classes = $this->getSetting('ul_classes');
        $option  = $this->getValue();
        $option  = ! is_null($option) ? $option : $default;
        $this->removeAttribute('name');
        $id = $this->getAttribute('id', '');
        $this->removeAttribute('id');
        $generator = new Html();
        $this->setAttribute('data-tr-field', $this->getContextId());
        $label_attr = '';
        $with = null;

        if($id) { $id = "id=\"{$id}\""; }

        if($mode == 'image') {
            $with = ['tabindex' => '-1'];
            $label_attr = 'class="tr-radio-options-label" tabindex="0"';
        }

        $classes = $mode == 'image' ? 'tr-radio-images tr-radio-options' : 'tr-data-full';
        $classes = $ul_classes ? $ul_classes . ' ' . $classes : $classes;

        $field = "<ul class=\"{$classes}\" {$id}>";

        foreach ($this->options as $key => $value) {
            $content = $key;
            $key = esc_attr($key);
            $label_attr_temp = $label_attr;

            if($mode == 'image') {
                $src = $value['src'];
                $value = $value['value']; // keep as last setter
                $label_attr_temp .= " title=\"{$key}\"";
                $content =  "<img src='{$src}' class='tr-radio-images-image' alt='{$key}' />";
            }

            if ( $option == $value && isset($option) ) {
                $this->setAttribute('checked', 'checked');
            } else {
                $this->removeAttribute('checked');
            }

            $field .= "<li><label {$label_attr_temp}>";
            $field .= $generator->input( 'radio', $name, $value, $this->getAttributes($with ?? null) );
            $field .= "<span>{$content}</span></label></li>";
        }

        $field .= '</ul>';

        return $field;
    }

    /**
     * Use images instead of text
     *
     * @param string $style options include `square` or `normal`
     * @param string $ul_classes css classes for ul html element
     *
     * @return $this
     */
    public function useImages($style = 'normal', $ul_classes = 'tr-round-image-corners')
    {
        $ul_classes .= $style == 'square' ? ' tr-radio-images-square' : ' tr-radio-images-normal';
        $this->settings['mode'] = 'image';
        $this->settings['ul_classes'] = $ul_classes;

        return $this;
    }

}