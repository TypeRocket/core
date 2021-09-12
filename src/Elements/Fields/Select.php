<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\OptionsTrait;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;

class Select extends Field
{

    use OptionsTrait, DefaultSetting, RequiredTrait;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'select' );
    }

    /**
     * Covert Select to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }
        
        $this->setupInputId();
        $default = $this->getSetting('default');
        $name_root = $this->getNameAttributeString();
        $this->setAttribute('data-tr-field', $this->getContextId());
        if( $this->getAttribute('multiple') ) {
            $name = $name_root . '[]';
            $this->setCast('array');
        } else {
            $name = $name_root;
            $this->setCast('string');
        }

        $this->setAttribute('name', $name);
        $option = $this->getValue(true);
        $option = ! is_null($option) ? $option : $default;

        // Use Chosen JS
        if($this->getSetting('search')) {
            $url = \TypeRocket\Core\Config::get('urls.typerocket');
            wp_enqueue_script( 'typerocket-chosen', $url . '/js/lib/chosen.min.js', ['jquery'], false, true );
            $this->attrClass('tr-chosen-select-js');

            if(!$this->getAttribute('data-placeholder')) {
                $this->setAttribute('data-placeholder', $this->getLabel() );
            }
        }

        $hidden = (string) Html::input('hidden', $name_root, '');
        $select = Html::select( $this->getAttributes() );

        foreach ($this->options as $key => $value) {
            $attr = [];
            if( is_array($value) ) {

                $optgroup = Html::optgroup(['label' => $key]);

                foreach($value as $k => $v) {

                    if(is_array($v)) { $v = null; }

                    if(is_array($option) && in_array($v, $option)) {
                        $attr['selected'] = 'selected';
                    } elseif ( !is_array($option) && $option == $v && isset($option) ) {
                        $attr['selected'] = 'selected';
                    } else {
                        unset( $attr['selected'] );
                    }

                    $attr['value'] = $v;
                    $optgroup->nest( Html::option( $attr, (string) $k) );
                }

                $select->nest( $optgroup );

            } else {
                if(is_array($value)) { $value = null; }

                if(is_array($option) && in_array($value, $option)) {
                    $attr['selected'] = 'selected';
                } elseif ( !is_array($option) && $option == $value && isset($option) ) {
                    $attr['selected'] = 'selected';
                } else {
                    unset( $attr['selected'] );
                }

                $attr['value'] = $value;

                $select->nest( Html::option( $attr, (string) $key) );
            }

        }

        return $hidden . $select;
    }

    /**
     * Use Chosen JS
     *
     * @link https://harvesthq.github.io/chosen/
     *
     * @param string|null $placeholder data-placeholder
     * @param bool $empty allow_single_deselect
     * @param int|null $threshold disable_search_threshold
     * @param int|null $max max_selected_options
     *
     * @return Select
     */
    public function searchable($placeholder = null, bool $empty = false, $threshold = null, $max = null)
    {
        if($threshold) {
            $this->setAttribute('data-threshold', $threshold);
        }

        if($empty) {
            $this->setAttribute('data-empty', 'yes');
        }

        if($placeholder) {
            $this->setAttribute('data-placeholder', $placeholder);
        }

        if($max) {
            $this->setAttribute('data-max', $max);
        }

        return $this->setSetting('search', true);
    }

    /**
     * Make select multiple
     *
     * @return $this
     */
    public function multiple()
    {
        return $this->setAttribute('multiple', 'multiple');
    }
}