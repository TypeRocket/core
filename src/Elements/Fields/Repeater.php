<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Traits\ControlsSetting;
use \TypeRocket\Html\Generator;

class Repeater extends Field implements ScriptField
{

	use ControlsSetting;

    protected $fields = [];
    protected $headline = null;

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
        $assetVersion = Config::locate('app.assets', '1.0');
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

        $headline = $this->headline ? '<h1>' . $this->headline . '</h1>': '';

        // add controls
        if (isset( $settings['help'] )) {
            $help = "<div class=\"help\"> <p>{$settings['help']}</p> </div>";
            $this->removeSetting( 'help' );
        } else {
            $help = '';
        }

        // add collapsed / contracted
        if(!empty($settings['contracted'])) {
            $fields_classes = ' tr-repeater-collapse';
        }

        // add button settings
        if (isset( $settings['add_button'] )) {
            $add_button_value = $settings['add_button'];
        } else {
            $add_button_value = "Add New";
        }

	    $controls = [
		    'contract' => 'Contract',
		    'flip' => 'Flip',
		    'clear' => 'Clear All',
		    'add' => $add_button_value,
	    ];

	    // controls settings
	    if (isset( $settings['controls'] ) && is_array($settings['controls']) ) {
		    $controls = array_merge($controls, $settings['controls']);
	    }

	    // escape controls
	    $controls = array_map(function($item) {
	    	return esc_attr($item);
	    }, $controls);

        // template for repeater groups
        $href          = '#remove';
        $openContainer = '<div class="repeater-controls"><div class="collapse"></div><div class="move"></div><a href="' . $href . '" class="remove" title="remove"></a></div><div class="repeater-inputs">';
        $endContainer  = '</div>';

        $html .= '<div class="control-section tr-repeater">'; // start tr-repeater

        // setup repeater
        $cache_group = $form->getGroup();

        $root_group = $this->getDots();
        $form->setGroup( $this->getDots() . ".{{ {$name} }}" );

        // add controls (add, flip, clear all)
        $generator    = new Generator();
        $default_null = $generator->newInput( 'hidden', $this->getAttribute( 'name' ), null )->getString();

        $html .= "<div class=\"controls\"><div class=\"tr-repeater-button-add\"><input type=\"button\" value=\"{$controls['add']}\" class=\"button add\" /></div><div class=\"button-group\"><input type=\"button\" value=\"{$controls['flip']}\" class=\"flip button\" /><input type=\"button\" value=\"{$controls['contract']}\" class=\"tr_action_collapse button\"><input type=\"button\" value=\"{$controls['clear']}\" class=\"clear button\" /></div>{$help}<div>{$default_null}</div></div>";

        // replace name attr with data-name so fields are not saved
        $templateFields = str_replace( ' name="', ' data-name="', $this->getTemplateFields() );

        // render js template data
        $html .= "<div class=\"tr-repeater-group-template\" data-id=\"{$name}\">";
        $html .= $openContainer . $headline . $templateFields . $endContainer;
        $html .= '</div>';

        // render saved data
        $html .= '<div class="tr-repeater-fields'.$fields_classes.'">'; // start tr-repeater-fields
        $repeats = $this->getValue();
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
     * @param $field
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
     * Make repeater contracted by default
     *
     * @return $this
     */
    public function contracted()
    {
        return $this->setSetting('contracted', true);
    }

}

