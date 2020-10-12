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
    protected $componentFolder = null;
    protected $confirmRemove = false;
    protected $paths;
    protected $urls;
    protected $sort = true;

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
        $this->urls = tr_config('urls');
        $this->paths = tr_config('paths');
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

        if(class_exists('TypeRocketPro\Features\EditorScripts')) {
            call_user_func('TypeRocketPro\Features\EditorScripts::enqueueEditorScripts');
        }
    }

    /**
     * Covert Matrix to HTML string
     */
    public function getString()
    {
        // enqueue tinymce
        echo '<div class="tr-control-section tr-divide tr-dummy-editor" style="display: none; visibility: hidden;">';
        wp_editor('', 'tr_dummy_editor');
        echo '</div>';

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
        $name = $this->getName();
        $options = $this->popAllOptions();
        $options = $options ?: tr_config("components.{$name}");
        $list = array_merge($options, $this->staticOptions);

        foreach ($list as $name) {
            $c = static::getComponentClass($name);
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
                    $class = static::getComponentClass($tr_matrix_type)->form($form)->data($form->getModel());
                    static::componentTemplate($class, $tr_matrix_group);
                }
            }

            $blocks = ob_get_clean();;

        }

        return trim($blocks);

    }

    /**
     * @param $type
     *
     * @return Component
     */
    public static function getComponentClass($type)
    {
        $component_class = tr_config("components.registry.{$type}");

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

        $group_control_list = apply_filters('tr_component_item_controls', $group_control_list);

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
        ?>
        <li data-tr-component="<?php echo $component->uuid(); ?>" tabindex="0" class="matrix-field-group tr-repeater-clones tr-repeater-group matrix-type-<?php echo esc_attr($component->registeredAs()); ?> matrix-group-<?php echo esc_attr($group); ?> <?php echo $classes; ?>">
            <div class="tr-repeater-controls">
                <?php echo $controls_html; ?>
            </div>
            <div class="tr-component-inputs tr-repeater-inputs">
                <?php
                echo "<h3>{$component->feature('nameable')}</h3>";
                $component->fields();
                ?>
            </div>
            <?php do_action('tr_component_include', 'matrix', $component, $group); ?>
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
     * Get component folder
     *
     * @return null|string
     */
    public function getComponentFolder()
    {
        if( ! $this->componentFolder ) {
            $this->componentFolder = $this->getName();
        }

        return $this->componentFolder;
    }

    /**
     * Set component folder
     *
     * @param string $folder_name
     *
     * @return $this
     */
    public function setComponentFolder($folder_name = '')
    {
        $dir = tr_config('paths.components') . '/' . $folder_name;

        if(file_exists($dir)) {
            $this->componentFolder = $folder_name;
        }

        return $this;
    }

}