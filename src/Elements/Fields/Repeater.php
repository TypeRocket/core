<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Traits\ControlsSetting;
use \TypeRocket\Html\Generator;
use TypeRocket\Html\Tag;

class Repeater extends Field implements ScriptField
{

    use ControlsSetting;

    protected $fields = [];
    protected $headline = null;
    protected $limit = 99999;
    protected $hide = [
        'move' => false,
        'remove' => false,
        'contract' => false,
        'clear' => false,
        'flip' => false,
    ];

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'repeater' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts()
    {
        $assetVersion = Config::locate('app.assets');
        wp_enqueue_script( 'jquery-ui-sortable', ['jquery'], $assetVersion, true );
    }

    /**
     * Covert Repeater to HTML string
     */
    public function getString()
    {
        $this->setAttribute( 'name', $this->getNameAttributeString() );
        $form = $this->getForm();
        $settings = $this->getSettings();
        $name     = $this->getName();
        $form->setDebugStatus( false );
        $html =  $fields_classes = '';
        $repeats = $this->getValue();
        $num_repeaters = count( is_countable($repeats) ? $repeats : []);

        $headline = $this->headline ? '<h1>' . $this->headline . '</h1>': '';

        // add controls
        if (isset( $settings['help'] )) {
            $help = "<div class=\"tr-form-field-help\"> <p>{$settings['help']}</p> </div>";
            $this->removeSetting( 'help' );
        } else {
            $help = '';
        }

        // add button settings
        if (isset( $settings['add_button'] )) {
            $add_button_value = $settings['add_button'];
        } else {
            $add_button_value = "Add New";
        }

        $controls = [
            'contract' => 'Contract',
            'expand' => 'Expand',
            'flip' => 'Flip',
            'clear' => 'Clear All',
            'add' => $add_button_value,
            'limit' => 'Limit Hit',
        ];

        // controls settings
        if (isset( $settings['controls'] ) && is_array($settings['controls']) ) {
            $controls = array_merge($controls, $settings['controls']);
        }

        // escape controls
        $controls = array_map(function($item) {
            return esc_attr($item);
        }, $controls);

        // add collapsed / contracted
        $expanded = 'tr-repeater-expanded';
        $expand_label = $controls['contract'];
        if(!empty($settings['contracted'])) {
            $fields_classes = ' tr-repeater-collapse';
            $expanded = 'tr-repeater-contacted';
            $expand_label = $controls['expand'];
        }

        // template for repeater groups
        $control_list = [
            'contract' => ['class' => 'collapse tr-control-icon tr-control-icon-collapse'],
            'move' => ['class' => 'move tr-control-icon tr-control-icon-move'],
            'remove' => ['class' => 'remove tr-control-icon tr-control-icon-remove', 'href' => '#remove', 'title' => __('remove', 'typerocket-domain')],
        ];

        foreach ($this->hide as $control_name => $hide) {
            if($hide) { unset($control_list[$control_name]); }
        }

        $controls_html = array_reduce($control_list, function($carry, $item) {
            return $carry . Tag::make('a', $item);
        });

        $openContainer = '<div class="repeater-controls">'.$controls_html.'</div><div class="repeater-inputs">';
        $endContainer  = '</div>';

        $html .= '<div class="tr-repeater">'; // start tr-repeater

        // setup repeater
        $cache_group = $form->getGroup();

        $root_group = $this->getDots();
        $form->setGroup( $this->getDots() . ".{{ {$name} }}" );

        // add controls (add, flip, clear all)
        $generator    = new Generator();
        $default_null = $generator->newInput( 'hidden', $this->getAttribute( 'name' ), null, $this->getAttributes() )->getString();

        // main controls
        $control_list = [
            'flip' => ['class' => 'flip button', 'value' => $controls['flip'] ],
            'contract' => ['class' => "tr_action_collapse button {$expanded}", 'value' => $expand_label, 'data-contract' => $controls['contract'], 'data-expand' => $controls['expand']],
            'clear' => ['class' => 'clear button', 'value' => $controls['clear'] ],
        ];

        foreach ($this->hide as $control_name => $hide) {
            if($hide) { unset($control_list[$control_name]); }
        }

        $controls_html = array_reduce($control_list, function($carry, $item) {
            return $carry . Tag::make('input', array_merge(['type' => 'button'], $item));
        });
        $add_value = $num_repeaters < $this->limit ? $controls['add'] : $controls['limit'];
        $add_class = $num_repeaters < $this->limit ? 'button add' : 'button disabled add';
        $add_button = Tag::make('input', ['type' => 'button', 'value' => $add_value, 'class' => $add_class, 'data-add' => $controls['add'], 'data-limit' => $controls['limit']]);

        $html .= "<div class=\"controls\"><div class=\"tr-d-inline tr-mr-10\">{$add_button}</div><div class=\"button-group\">{$controls_html}</div>{$help}<div>{$default_null}</div></div>";

        // replace name attr with data-name so fields are not saved
        $templateFields = str_replace( ' name="', ' data-name="', $this->getTemplateFields() );

        // render js template data
        $html .= "<div class=\"tr-repeater-group-template\" data-id=\"{$name}\" data-limit=\"{$this->limit}\">";
        $html .= $openContainer . $headline . $templateFields . $endContainer;
        $html .= '</div>';

        // render saved data
        $html .= '<div class="tr-repeater-fields'.$fields_classes.'">'; // start tr-repeater-fields

        if ( is_array( $repeats ) ) {
            foreach ($repeats as $k => $array) {
                $html .= '<div class="tr-repeater-group">';
                $html .= $openContainer;
                $form->setGroup( $root_group . ".{$k}" );
                $html .= $headline;
                $html .= $form->getFromFieldsString( $this->fields );
                $html .= $endContainer;
                $html .= '</div>';
            }
        }
        $html .= '</div>'; // end tr-repeater-fields
        $form->setGroup( $cache_group );
        $html .= '</div>'; // end tr-repeater

        return $html;
    }

    /**
     * Get the repeater template field for JS hook
     *
     * @return string
     */
    private function getTemplateFields()
    {
        return $this->getForm()->setDebugStatus(false)->getFromFieldsString( $this->fields );
    }

    /**
     * Set fields for repeater
     *
     * @param array $fields
     *
     * @return $this
     */
    public function setFields( $fields )
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Append field
     *
     * @param string $field
     *
     * @return $this
     */
    public function appendField( $field )
    {
        if(is_array($field) || $field instanceof Field) {
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * Get Fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set headline for the repeater groups
     *
     * @param null $headline
     *
     * @return $this
     */
    public function setHeadline($headline = null) {
        $this->headline = $headline;

        return $this;
    }

    /**
     * Get repeater group headline
     *
     * @return null
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Hide Item Control
     *
     * @param string $name
     * @return $this
     */
    public function hideControl($name)
    {
        array_key_exists($name, $this->hide) ? $this->hide[$name] = true : null;
        return $this;
    }

    /**
     * Show Item Control
     *
     * @param string $name
     * @return $this
     */
    public function showControl($name)
    {
        array_key_exists($name, $this->hide) ? $this->hide[$name] = false : null;
        return $this;
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
     * Make repeater contracted by default
     *
     * @return $this
     */
    public function contracted()
    {
        return $this->setSetting('contracted', true);
    }

}
