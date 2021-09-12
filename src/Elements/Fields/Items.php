<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\Limits;
use TypeRocket\Html\Html;

class Items extends Field implements ScriptField
{
    use Limits;

    public $inputType = 'text';

    /**
     * Get the scripts
     */
    public function enqueueScripts()
    {
        wp_enqueue_script('jquery-ui-sortable', ['jquery'], false, true );
    }

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'items' );
    }

    /**
     * Covert Items to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name = $this->getNameAttributeString();
        $this->attrClass( 'items-list' );
        $items = $this->setCast('array')->getValue();
        $this->removeAttribute('name');
        $settings = $this->getSettings();
        $num_items = count( is_countable($items) ? $items : []);

        // add button settings
        if (isset( $settings['button'] )) {
            $add_button_value = $settings['button'];
        } else {
            $add_button_value = "Add New";
        }

        $controls = [
            'clear' => 'Clear All',
            'add' => $add_button_value,
            'limit' => 'Limit Hit',
        ];

        $limit = __('Limit', 'typerocket-domain');

        // controls settings
        if (isset( $settings['controls'] ) && is_array($settings['controls']) ) {
            $controls = array_merge($controls, $settings['controls']);
        }

        $list = [];

        if (is_array( $items )) {
            foreach ($items as $value) {
                $remove = '#remove';
                $remove_title = __('Remove Item', 'typerocket-domain');
                $list[] = Html::li(['class' => 'tr-items-list-item', 'tabindex' => '0'], [
                    '<a class="move tr-control-icon tr-control-icon-move"></a>',
                    Html::input( $this->inputType, $name.'[]', $this->sanitize($value, 'raw') ),
                    Html::el('a', ['href' => $remove, 'class' => 'remove tr-control-icon tr-items-list-item-remove tr-control-icon-remove', 'title' => $remove_title]),
                ]);
            }
        }

        $this->removeAttribute('id');
        $html = $this->limit < 99999 ? "<p class=\"tr-field-help-top\">{$limit} {$this->limit}</p>" : '';
        $html .= (string) Html::input( 'hidden', $name, '0', $this->getAttributes() );

        $add_buttom = $num_items < $this->limit ? $controls['add'] : $controls['limit'];

        $prepend = Html::input('button', null, $add_buttom, [
            'class' => ($num_items < $this->limit ? '' : 'disabled') . 'tr-items-list-button tr-items-prepend button',
            'data-add' => $controls['add'],
            'data-limit' => $controls['limit'],
        ]);

        $append = Html::input('button', null, $add_buttom, [
            'class' => ($num_items < $this->limit ? '' : 'disabled') . 'tr-items-list-button tr-items-append button',
            'data-add' => $controls['add'],
            'data-limit' => $controls['limit'],
        ]);

        $clear = Html::input('button', null, $controls['clear'], [
            'class' => 'tr-items-list-clear button',
        ]);

        $html .= Html::div(['class' => 'button-group'], [
            $prepend,
            $clear
        ]);

        if (is_null( $name ) && is_string( $this->getAttribute('data-tr-name') )) {
            $name = $this->getAttribute('data-tr-name');
        }

        $html .= Html::ul([
            'data-tr-name' => $name,
            'data-type' => $this->inputType,
            'data-limit' => $this->limit,
            'class'     => 'tr-items-list cf'
        ], $list );

        $html .= $append;

        return $html;
    }

    /**
     * Set Input Type
     *
     * @param string $type
     * @return $this
     */
    public function setInputType($type)
    {
        $this->inputType = $type;

        return $this;
    }

    /**
     * Get Input Types
     *
     * @return string
     */
    public function getInputType()
    {
        return $this->inputType;
    }

    /**
     * Set Controls settings
     *
     * @param array $controls options include: flip, clear, add, contract
     *
     * @return mixed
     */
    public function setControls( array $controls ) {
        return $this->setSetting('controls', $controls);
    }

    /**
     * Set Control Limit
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setControlLimit( $value ) {
        return $this->appendToArraySetting('controls', 'limit', $value);
    }

    /**
     * Set Control Add
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setControlAdd( $value ) {
        return $this->appendToArraySetting('controls', 'add', $value);
    }

    /**
     * Set Control Clear
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setControlClear( $value ) {
        return $this->appendToArraySetting('controls', 'clear', $value);
    }


}
