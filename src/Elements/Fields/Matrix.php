<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\ControlsSetting;
use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\OptionsTrait;
use TypeRocket\Html\Html;
use TypeRocket\Models\WPPost;
use TypeRocket\Template\Component;
use TypeRocket\Template\ErrorComponent;

class Matrix extends Field implements ScriptField
{
    use OptionsTrait, DefaultSetting, ControlsSetting;

    protected $staticOptions = [];
    protected $componentGroup = null;
    protected $confirmRemove = false;
    protected $paths;
    protected $urls;
    protected $sort = true;
    protected static $editorAdded = false;
    public const TEMPLATE_TYPE = 'matrix';

    /**
     * Define debug function
     *
     * @return string
     */
    protected function debugHelperFunction()
    {
        if($this->getModel() instanceof WPPost) {
            return "tr_components_field('{$this->getDots()}');";
        }
        $class = get_class($this->getModel());
        return "tr_components_field('{$this->getDots()}', '{$this->getForm()->getItemId()}', \\{$class}::class);";
    }

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'matrix' );
        $this->urls = \TypeRocket\Core\Config::get('urls');
        $this->paths = \TypeRocket\Core\Config::get('paths');
    }

    /**
     * Add Static Options
     *
     * Name => file-name (do not use file extension)
     *
     * @param array $options
     * @return Matrix
     */
    public function addStaticOptions(array $options)
    {
        $this->staticOptions = $options;

        return $this;
    }

    /**
     * Enable Sort
     *
     * @return $this
     */
    public function enableSort()
    {
        $this->sort = true;

        return $this;
    }

    /**
     * Disable Sort
     *
     * @return $this
     */
    public function disableSort()
    {
        $this->sort = false;

        return $this;
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts()
    {
        wp_enqueue_script( 'jquery-ui-sortable', [ 'jquery' ], false, true );
        wp_enqueue_script( 'jquery-ui-datepicker', [ 'jquery' ], false, true );
        wp_enqueue_script( 'wp-color-picker' );

        if(class_exists('\TypeRocketPro\Core\AdvancedSystem')) {
            call_user_func('\TypeRocketPro\Elements\Traits\EditorScripts::enqueueEditorScripts');
        }
    }

    /**
     * Covert Matrix to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        // enqueue tinymce
        static::dummyEditor();

        $this->setAttribute('name', $this->getNameAttributeString());
        $fields_classes = '';

        // add button settings
        $controls = [
            'contract' => __('Contract', 'typerocket-domain'),
            'expand' => __('Expand', 'typerocket-domain'),
            'clone' => __('Duplicate', 'typerocket-domain'),
            'move' => __('Move', 'typerocket-domain'),
            'flip' => __('Flip', 'typerocket-domain'),
            'clear' => __('Clear All', 'typerocket-domain'),
            'add' => __('Add New', 'typerocket-domain'),
        ];

        // setup select list of files
        $select = $this->getSelectHtml();
        $group = $this->getName();
        $settings = $this->getSettings();
        $blocks = $this->getMatrixBlocks();

        // add controls
        if (isset( $settings['help'] )) {
            $help = "<div class=\"tr-form-field-help\"> <p>{$settings['help']}</p> </div>";
            $this->removeSetting('help');
        } else {
            $help = '';
        }

        $generator = new Html();
        $default_null = $generator->input('hidden', $this->getAttribute('name'), null)->getString();


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

        $controls_buttons = [
            'flip' => "<input type=\"button\" value=\"{$controls['flip']}\" class=\"tr-repeater-action-flip button\">",
            'contract' => "<input type=\"button\" value=\"{$expand_label}\" data-contract=\"{$controls['contract']}\" data-expand=\"{$controls['expand']}\" class=\"tr-repeater-action-collapse button {$expanded}\">",
            'clear' => "<input type=\"button\" value=\"{$controls['clear']}\" class=\"tr-repeater-action-clear button\">",
        ];

        $remove_class = $this->confirmRemove ? 'tr-repeater-confirm-remove' : '';

        foreach ($this->hide as $control_name => $hide) {
            if($hide) {
                $fields_classes .= ' tr-repeater-hide-' . $control_name;
                if($controls_buttons[$control_name] ?? null) {
                    unset($controls_buttons[$control_name]);
                }
            }
        }

        $controls_html = array_reduce($controls_buttons, function($carry, $item) {
            return $carry . $item;
        });

        // add it all
        $home_url = esc_url( home_url('/', is_ssl() ? 'https' : 'http') );
        return "
<div class='tr-matrix tr-repeater'>
<div class='tr-matrix-controls controls'>
{$select}
<div class=\"tr-mr-10 tr-d-inline\">
<input type=\"button\" value=\"{$controls['add']}\" data-root=\"{$home_url}\" data-tr-group=\"{$group}\" class=\"button tr-matrix-add-button\">
</div>
<div class=\"button-group\">
{$controls_html}
</div>
{$help}
</div>
<div>{$default_null}</div>
<ul class='tr-matrix-fields tr-repeater-fields ui-sortable {$fields_classes} {$remove_class}'>{$blocks}</ul></div>";
    }

    /**
     * Set the select list for components
     *
     * @return string
     */
    protected function getSelectHtml()
    {
        $name = $this->getName();
        $options = $this->loadComponentsIntoOptions()->getOptions();

        if($this->sort) {
            ksort($options);
        }

        if ($options) {
            $generator = new Html();
            $generator->el( 'select', [
                'class' => "tr-mr-10 tr-d-inline matrix-select matrix-select-{$name}",
                'data-group' => $this->getGroupWithFrom(),
            ]);
            $default = $this->getSetting('default');

            /**
             * @var string $title
             * @var Component $component */
            foreach ($options as $title => $component) {
                $value = $component->registeredAs();
                $attr['value'] = $value;
                if ( $default == $value && isset($default) ) {
                    $attr['selected'] = 'selected';
                } else {
                    unset( $attr['selected'] );
                }

                $generator->nest( Html::option($attr, $title) );
            }

            $select = $generator->getString();

        } else {
            $select = "<div class=\"tr-dev-alert-helper\"><i class=\"icon dashicons dashicons-editor-code\"></i> You need to register a components group for {$name}.</div>";
        }

        return $select;
    }

    /**
     * Set options from folder
     *
     * @return $this
     */
    public function loadComponentsIntoOptions()
    {
        $name = $this->getComponentGroup();
        $options = $this->popAllOptions();
        $options = $options ?: \TypeRocket\Core\Config::get("components.{$name}");
        $options = apply_filters('typerocket_component_options', $options, $name, $this);

        $list = array_merge($options, $this->staticOptions);

        foreach ($list as $name) {
            $c = static::getComponentClass($name, $name);
            $this->options[$c->title()] = $c;
        }

        return $this;
    }

    /**
     * Get component block
     *
     * @return string
     */
    protected function getMatrixBlocks()
    {
        // add button settings
        $val = $this->setCast('array')->getValue();
        $blocks = '';
        $form = $this->getForm()->clone();

        if (is_array( $val )) {

            ob_start();

            foreach ($val as $tr_matrix_key => $data) {
                foreach ($data as $tr_matrix_type => $fields) {

                    $tr_matrix_group = $this->getName();
                    $tr_matrix_type  = lcfirst( $tr_matrix_type );
                    $root_group        = $this->getGroupWithFrom();
                    $form->setDebugStatus(false);
                    $append_group = $root_group;

                    if($root_group) {
                        $append_group .= '.';
                    }

                    $form->setGroup($append_group . "{$tr_matrix_group}.{$tr_matrix_key}.{$tr_matrix_type}");
                    $class = static::getComponentClass($tr_matrix_type, $tr_matrix_group)->form($form)->data($form->getModel());
                    static::componentTemplate($class, $tr_matrix_group);
                }
            }

            $blocks = ob_get_clean();;

        }

        return trim($blocks);

    }

    /**
     * @param $type
     * @param null|string $group
     *
     * @return Component
     */
    public static function getComponentClass($type, $group = null)
    {
        // This is to help with migration from v4/v1 to v5
        $reg = \TypeRocket\Core\Config::get("components.registry");
        $component_class = $reg["{$group}:{$type}"] ?? $reg[$type] ?? null;

        $component_class = apply_filters('typerocket_component_class', $component_class, $type, $group, $reg);

        if(!$component_class) {
            return (new ErrorComponent)->title($type)->registeredAs($type);
        }

        return (new $component_class)->registeredAs($type);
    }

    /**
     * @param Component $component
     * @param string $group
     * @param string $classes
     */
    public static function componentTemplate($component, $group, $classes = '')
    {
        $group_control_list = [
            'contract' => ['class' => 'tr-repeater-collapse tr-control-icon tr-control-icon-collapse', 'title' => __('Contract', 'typerocket-domain'), 'tabindex' => '0'],
            'move' => ['div', 'class' => 'move tr-control-icon tr-control-icon-move', 'title' => __('Move')],
            'clone' => null,
            'remove' => ['class' => "tr-repeater-remove tr-control-icon tr-control-icon-remove", 'title' => __('Remove', 'typerocket-domain'), 'tabindex' => '0'],
        ];

        $group_control_list = apply_filters('typerocket_component_item_controls', $group_control_list);

        if(!$component->feature('cloneable')) {
            $group_control_list['clone'] = null;
        }

        foreach ($group_control_list as $control_name => $options)
        {
            if(!$options) {
                unset($group_control_list[$control_name]);
            }
        }

        $controls_html = array_reduce($group_control_list, function($carry, $item) {
            $el = isset($item[0]) ? $item[0] : 'a';
            unset($item[0]);
            return $carry . Html::el( $el, $item);
        });

        $name = $component->feature('nameable');
        $name = apply_filters('typerocket_component_name', $name, static::TEMPLATE_TYPE, $component, $group, $classes);
        ?>
        <li data-tr-component="<?php echo $component->uuid(); ?>" tabindex="0" class="matrix-field-group tr-repeater-clones tr-repeater-group matrix-type-<?php echo esc_attr($component->registeredAs()); ?> matrix-group-<?php echo esc_attr($group); ?> <?php echo $classes; ?>">
            <div class="tr-repeater-controls">
                <?php echo $controls_html; ?>
            </div>
            <div class="tr-component-inputs tr-repeater-inputs">
                <?php
                echo "<h3 class=\"tr-component-group-name\">{$name}</h3>";
                $component->fields();
                ?>
            </div>
            <?php do_action('typerocket_component_include', 'matrix', $component, $group); ?>
        </li>
        <?php
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
     * Get component group
     *
     * @return null|string
     */
    public function getComponentGroup()
    {
        if( ! $this->componentGroup ) {
            $this->componentGroup = $this->getName();
        }

        return $this->componentGroup;
    }

    /**
     * Set component folder
     *
     * @param string $folder_name
     *
     * @return $this
     */
    public function setComponentGroup($folder_name = '')
    {
        $this->componentGroup = $folder_name;

        return $this;
    }

    public static function dummyEditor()
    {
        if(!static::$editorAdded) {
            static::$editorAdded = true;
            echo '<div class="tr-control-section tr-divide tr-dummy-editor" style="display: none; visibility: hidden;">';
            wp_editor('', 'tr_dummy_editor');
            echo '</div>';
        }
    }

    /**
     * Loop Components
     *
     * @param array $builder_data
     * @param array $other be sure to pass $name, $item_id, $model
     * @param string $group
     */
    public static function componentsLoop($builder_data, $other = [], $group = 'builder') {
        /**
         * @var $name
         * @var $item_id
         * @var $model
         * @var $nested
         */
        extract($other);
        $model = $model ?? null;
        $item_id = $item_id ?? null;
        $nested = $nested ?? false;
        $i = $nested ? 1 : 0;
        $info = $info ?? null; // This is an open variable for misc data
        $group = $name ?? $group; // This is to help with migration from v4/v1 to v5
        $name = $group;
        $len = count($builder_data);

        do_action('typerocket_components_loop', $builder_data, $other, $len);
        foreach ($builder_data as $hash => $data) {
            $first_item = $last_item = false;

            if ($i == 0) {
                $first_item = true;
            } else if ($i == $len - 1) {
                $last_item = true;
            }

            $component_id = key($data);
            $component = strtolower(key($data));
            $vals = compact('name', 'item_id', 'model', 'first_item', 'last_item', 'component_id', 'hash', 'info');
            $class = static::getComponentClass($component, $group);
            $class->render($data[$component_id], $vals);
            $i++;
        }
    }

}