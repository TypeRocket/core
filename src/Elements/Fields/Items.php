<?php
namespace TypeRocket\Elements\Fields;

use \TypeRocket\Html\Generator;

class Items extends Field
{
    public $limit = 99999;
    public $inputType = 'text';

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
        $name = $this->getNameAttributeString();
        $this->appendStringToAttribute( 'class', 'items-list' );
        $items = $this->getValue();
        $this->removeAttribute('name');
        $generator = Generator::make();
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

        // controls settings
        if (isset( $settings['controls'] ) && is_array($settings['controls']) ) {
            $controls = array_merge($controls, $settings['controls']);
        }

        $list = '';

        if (is_array( $items )) {
            foreach ($items as $value) {

                $value = esc_attr( $this->sanitize($value, 'raw') );
                $input = $generator->newInput( $this->inputType, $name . '[]', $value )->getString();
                $remove = '#remove';
                $remove_title = __('Remove Item', 'typerocket-domain');
                $list .= $generator->newElement( 'li', ['class' => 'item'],
                    '<a class="move tr-control-icon tr-control-icon-move"></a><a href="'.$remove.'" class="remove tr-control-icon tr-control-icon-remove" title="'.$remove_title.'"></a>' . $input )->getString();

            }
        }

        $this->removeAttribute('id');
        $html = $generator->newInput( 'hidden', $name, '0', $this->getAttributes() )->getString();
        $html .= '<div class="button-group">';
        $html .= $generator->newElement( 'input', [
            'type'  => 'button',
            'class' => $num_items < $this->limit ? 'items-list-button button' : 'items-list-button disabled button',
            'data-add' => $controls['add'],
            'data-limit' => $controls['limit'],
            'value' => $num_items < $this->limit ? $controls['add'] : $controls['limit']
        ])->getString();
        $html .= $generator->newElement( 'input', [
            'type'  => 'button',
            'class' => 'items-list-clear button',
            'value' => $controls['clear']
        ])->getString();
        $html .= '</div>';

        if (is_null( $name ) && is_string( $this->getAttribute('data-name') )) {
            $name = $this->getAttribute('data-name');
        }

        $html .= $generator->newElement( 'ul', [
            'data-name' => $name,
            'data-type' => $this->inputType,
            'data-limit' => $this->limit,
            'class'     => 'tr-items-list cf'
        ], $list )->getString();

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
     * Limit Number of Items
     *
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit = 99999)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Get Item Limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
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
