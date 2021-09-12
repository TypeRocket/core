<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\BaseForm;
use TypeRocket\Elements\Traits\ControlsSetting;
use TypeRocket\Elements\Traits\Fieldable;
use TypeRocket\Elements\Traits\Limits;
use TypeRocket\Html\Html;
use TypeRocket\Utility\Str;

class Repeater extends Field implements ScriptField
{
    use ControlsSetting, Fieldable, Limits;

    protected $title = null;
    protected $confirmRemove = false;
    protected $bottomButton = true;

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
        wp_enqueue_script('jquery-ui-sortable', ['jquery'], false, true );
    }

    /**
     * Covert Repeater to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $this->setAttribute( 'name', $this->getNameAttributeString() );
        $form = $this->getForm()->clone()->setDebugStatus( false );
        $settings = $this->getSettings();
        $name     = $this->getName();
        $html =  $fields_classes = '';
        $repeats = $this->setCast('array')->getValue();
        $num_repeaters = count( is_countable($repeats) ? $repeats : []);
        $fields_num = count($this->fields ?? []);

        $headline = $this->title ? '<h2>' . $this->title . '</h2>': '';

        // add controls
        if (isset( $settings['help'] )) {
            $help = "<div class=\"tr-form-field-help\"> <p>{$settings['help']}</p> </div>";
            $this->removeSetting( 'help' );
        } else {
            $help = '';
        }

        $controls = [
            'contract' => __('Contract', 'typerocket-domain'),
            'expand' => __('Expand', 'typerocket-domain'),
            'flip' => __('Flip', 'typerocket-domain'),
            'clone' => __('Clone', 'typerocket-domain'),
            'clear' => __('Clear All', 'typerocket-domain'),
            'add' => __('Add New', 'typerocket-domain'),
            'limit' => __('Limit Hit', 'typerocket-domain'),
        ];

        $limit = __('Limit', 'typerocket-domain');

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
        $group_control_list = [
            'contract' => ['class' => 'tr-repeater-collapse tr-control-icon tr-control-icon-collapse', 'title' => __('Contract', 'typerocket-domain'), 'tabindex' => '0'],
            'move' => ['div', 'class' => 'move tr-control-icon tr-control-icon-move', 'title' => __('Move')],
            'clone' => null,
            'remove' => ['class' => "tr-repeater-remove tr-control-icon tr-control-icon-remove", 'title' => __('Remove', 'typerocket-domain'), 'tabindex' => '0'],
        ];

        $group_control_list = apply_filters('typerocket_repeater_item_controls', $group_control_list, $this);

        foreach ($group_control_list as $control_name => $options)
        {
            if(!$options || ($this->hide[$control_name] ?? true)) {
                $fields_classes .= ' tr-repeater-hide-' . $control_name;
                unset($group_control_list[$control_name]);
            }
        }

        $controls_html = array_reduce($group_control_list, function($carry, $item) {
            $el = isset($item[0]) ? $item[0] : 'a';
            unset($item[0]);
            return $carry . Html::el( $el, $item);
        });

        $openContainer = '<div class="tr-repeater-controls">'.$controls_html.'</div><div class="tr-repeater-inputs">';
        $endContainer  = '</div>';

        $html .= '<div class="tr-repeater">'; // start tr-repeater

        // add controls (add, flip, clear all)
        $default_null = Html::input( 'hidden', $this->getAttribute( 'name' ), null, $this->getAttributes() )->getString();

        // main controls
        $control_list = [
            'flip' => ['class' => 'tr-repeater-action-flip button', 'value' => $controls['flip'], 'title' => $controls['flip'] ],
            'contract' => ['class' => "tr-repeater-action-collapse button {$expanded}", 'value' => $expand_label, 'title' => $controls['contract'], 'data-contract' => $controls['contract'], 'data-expand' => $controls['expand']],
            'clear' => ['class' => 'tr-repeater-action-clear button', 'value' => $controls['clear'], 'title' => $controls['clear'] ],
        ];

        apply_filters('typerocket_repeater_controls', $control_list, $this);

        foreach ($this->hide as $control_name => $hide) {
            if($hide) { unset($control_list[$control_name]); }
        }

        $controls_html = array_reduce($control_list, function($carry, $item) {
            return $carry . Html::el('input', array_merge(['type' => 'button'], $item));
        });

        $add_value = $num_repeaters < $this->limit ? $controls['add'] : $controls['limit'];
        $add_class = $num_repeaters < $this->limit ? '' : 'disabled';
        $add_class = $add_class . ' button tr-repeater-action-add-button';
        $add_button = Html::el('input', ['type' => 'button', 'value' => $add_value, 'class' => $add_class . ' tr-repeater-action-add', 'data-add' => $controls['add'], 'data-limit' => $controls['limit']]);
        $html .= $this->limit < 99999 ? "<p class=\"tr-field-help-top\">{$limit} {$this->limit}</p>" : '';
        $html .= "<div class=\"controls\"><div class=\"tr-d-inline tr-mr-10\">{$add_button}</div><div class=\"button-group tr-d-inline\">{$controls_html}</div>{$help}<div>{$default_null}</div></div>";

        // replace name attr with data-tr-name so fields are not saved
        $templateFields = str_replace( ' name="', ' data-tr-name="', $this->getTemplateFields($form, $name) );

        // render js template data
        $classes = Str::classNames('tr-repeater-group', [
            'tr-repeater-clones' => !empty($group_control_list['clone'])
        ]);

        $html .= '<ul class="tr-repeater-group-template">';
        $html .= "<li tabindex=\"0\" data-id=\"{$name}\" data-fields='{$fields_num}' data-limit=\"{$this->limit}\" class=\"{$classes}\">";
        $html .= $openContainer . $headline . $templateFields . $endContainer;
        $html .= '</li>';
        $html .= '</ul>';

        $remove_class = $this->confirmRemove ? 'tr-repeater-confirm-remove' : '';

        $append = '';

        if(!$this->hide['append']) {
            $append = Html::button(['class' => $add_class . ' tr-repeater-action-add-append', 'data-add' => $controls['add'], 'data-limit' => $controls['limit']], $add_value);
        }

        // render saved data
        $html .= "<ol class='tr-repeater-fields {$fields_classes} {$remove_class}'>"; // start tr-repeater-fields
        if ( is_array( $repeats ) ) {
            foreach ($repeats as $k => $array) {
                $html .= "<li tabindex=\"0\" data-id=\"{$name}\" data-fields=\"{$fields_num}\" data-limit=\"{$this->limit}\" class=\"{$classes}\">";
                $html .= $openContainer;
                $html .= $headline;
                $html .= $form->super( $k, $this )->setFields($this->fields)->getFieldsString();
                $html .= $endContainer;
                $html .= '</li>';
            }
        }
        $html .= '</ol>'; // end tr-repeater-fields
        $html .= $append;
        $html .= '</div>'; // end tr-repeater

        return $html;
    }

    /**
     * Confirm Remove
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function confirmRemove($bool = true)
    {
        $this->confirmRemove = $bool;

        return $this;
    }

    /**
     * Get the repeater template field for JS hook
     *
     * @param BaseForm $form
     * @param $name
     *
     * @return string
     */
    protected function getTemplateFields(BaseForm $form, $name)
    {
        return $form->super( "{{ {$name} }}", $this )->setFields($this->fields)->getFieldsString();
    }

    /**
     * Set title for the repeater groups
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title) {
        $this->title = esc_html($title);

        return $this;
    }

    /**
     * Get repeater group headline
     *
     * @return null
     */
    public function getTitle()
    {
        return $this->title;
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

}
