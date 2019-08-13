<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\ControlsSetting;
use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Elements\Traits\OptionsTrait;
use \TypeRocket\Html\Generator;
use \TypeRocket\Core\Config;
use TypeRocket\Models\WPPost;
use \TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Buffer;

class Matrix extends Field implements ScriptField {

    use OptionsTrait, DefaultSetting, ControlsSetting;

    protected $mxid = null;
    protected $staticOptions = [];
    protected $componentFolder = null;
    protected $paths;
    protected $sort = true;
    protected $hide = [
        'move' => false,
        'remove' => false,
        'contract' => false,
        'clear' => false,
        'flip' => false,
    ];

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
        return "tr_components_field('{$this->getDots()}', '{$this->getItemId()}', \\{$class}::class);";
    }

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->mxid = md5( microtime( true ) ); // set id for matrix random
        $this->setType( 'matrix' );
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
    public function enqueueScripts() {
        $this->paths = Config::locate('paths');
        $assetVersion = Config::locate('app.assets');
        $assets = $this->paths['urls']['assets'];
        wp_enqueue_script( 'jquery-ui-sortable', [ 'jquery' ], $assetVersion, true );
        wp_enqueue_script( 'jquery-ui-datepicker', [ 'jquery' ], $assetVersion, true );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_script( 'typerocket-editor', $assets . '/typerocket/js/lib/redactor.min.js', ['jquery'], $assetVersion, true );
    }

    /**
     * Covert Matrix to HTML string
     */
    public function getString()
    {
        // enqueue tinymce
        echo '<div style="display: none; visibility: hidden;">';
        wp_editor('', 'tr_dummy_editor');
        echo '</div>';

        $this->setAttribute('name', $this->getNameAttributeString());

        // setup select list of files
        $select = $this->getSelectHtml();
        $folder = $this->getComponentFolder();
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

        $generator = new Generator();
        $default_null = $generator->newInput('hidden', $this->getAttribute('name'), null)->getString();

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

        $controls_buttons = [
            'flip' => "<input type=\"button\" value=\"{$controls['flip']}\" class=\"flip button\">",
            'contract' => "<input type=\"button\" value=\"{$expand_label}\" data-contract=\"{$controls['contract']}\" data-expand=\"{$controls['expand']}\" class=\"tr_action_collapse button {$expanded}\">",
            'clear' => "<input type=\"button\" value=\"{$controls['clear']}\" class=\"clear button\">"
        ];

        foreach ($this->hide as $control_name => $hide) {
            if($hide) { unset($controls_buttons[$control_name]); }
        }

        $controls_html = array_reduce($controls_buttons, function($carry, $item) {
            return $carry . $item;
        });

        // add it all
        $home_url = esc_url( home_url('/', is_ssl() ? 'https' : 'http') );
        $html = "
<div class='tr-matrix tr-repeater'>
<div class='matrix-controls controls'>
{$select}
<div class=\"tr-mr-10 tr-d-inline\">
<input type=\"button\" value=\"{$controls['add']}\" data-root=\"{$home_url}\" data-id=\"{$this->mxid}\" data-group=\"{$group}\" data-folder=\"{$folder}\" class=\"button matrix-button\">
</div>
<div class=\"button-group\">
{$controls_html}
</div>
{$help}
</div>
<div>{$default_null}</div>
<div id=\"{$this->mxid}\" class='matrix-fields tr-repeater-fields ui-sortable'>{$blocks}</div></div>";

        return $html;
    }

    /**
     * Sanitize the file name for component
     *
     * @param string $name
     *
     * @return string
     */
    private function cleanFileName( $name )
    {

        $name = Sanitize::underscore($name);

        return ucwords( $name );
    }

    /**
     * Set the select list for components
     *
     * @return string
     */
    private function getSelectHtml()
    {

        $name = $this->getName();
        $folder = $this->getComponentFolder();
        $options = $this->getOptions();
        $options = $options ? $options : $this->setOptionsFromFolder()->getOptions();
        $options = array_merge($options, $this->staticOptions);
        $options = apply_filters('tr_component_select_list', $options, $folder, $name);

        if($this->sort) {
            ksort($options);
        }


        if ($options) {
            $generator = new Generator();
            $formGroup = $this->getGroup();
            $generator->newElement( 'select', [
                'data-mxid' => $this->mxid,
                'class' => "tr-mr-10 tr-d-inline matrix-select-{$name}",
                'data-group' => $formGroup
            ]);
            $default = $this->getSetting('default');

            foreach ($options as $name => $value) {

                $attr['value'] = $value;
                if ( $default == $value && isset($default) ) {
                    $attr['selected'] = 'selected';
                } else {
                    unset( $attr['selected'] );
                }

                $generator->appendInside( 'option', $attr, $name );
            }

            $select = $generator->getString();

        } else {
            $dir = $this->paths['components'] . '/' . $folder;
            $select = "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> Add a component folder at <code>{$dir}</code> and add your component files to it.</div>";
        }

        return $select;

    }

    /**
     * Set options from folder
     *
     * @return $this
     */
    public function setOptionsFromFolder() {
        $paths = Config::locate('paths');
        $folder = $this->getComponentFolder();
        $dir = $paths['components'] . '/' . $folder;

        if (file_exists( $dir )) {

            $files = preg_grep( '/^([^.])/', scandir( $dir ) );

            foreach ($files as $file) {

                $is_php_file = function($haystack) {
                    // search forward starting from end minus needle length characters
                    $needle = '.php';
                    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
                };

                if (file_exists( $dir . '/' . $file ) && $is_php_file($file) ) {

                    $the_file = $file;
                    $path = pathinfo( $file );
                    $key = $this->cleanFileName( $path['filename'] );
                    $line = fgets(fopen( $dir . '/' . $the_file, 'r'));
                    if( preg_match("/<[h|H]\\d>(.*)<\\/[h|H]\\d>/U", $line, $matches) ) {
                        $key = strip_tags($matches[1]);
                    }
                    $this->options[$key] = $path['filename'];
                }
            }

        }

        return $this;
    }

    /**
     * Get component block
     *
     * @return string
     */
    private function getMatrixBlocks()
    {

        $val = $this->getValue();
        $utility = new Buffer();
        $blocks = '';
        $form = $this->getForm();
        $paths = Config::locate('paths');
        $folder = $this->getComponentFolder();

        if (is_array( $val )) {

            $utility->startBuffer();

            foreach ($val as $tr_matrix_key => $data) {
                foreach ($data as $tr_matrix_type => $fields) {

                    $tr_matrix_group = $this->getName();
                    $tr_matrix_type  = lcfirst( $tr_matrix_type );
                    $root_group        = $this->getGroup();
                    $form->setDebugStatus(false);
                    $append_group = $root_group;

                    if($root_group) {
                        $append_group .= '.';
                    }

                    $form->setGroup($append_group . "{$tr_matrix_group}.{$tr_matrix_key}.{$tr_matrix_type}");
                    $file        = $paths['components'] . "/" . $folder . "/{$tr_matrix_type}.php";
                    $file = apply_filters('tr_component_file', $file, ['folder' => $folder, 'name' => $tr_matrix_type, 'view' => 'component']);
                    $classes = "matrix-field-group tr-repeater-group matrix-type-{$tr_matrix_type} matrix-group-{$tr_matrix_group}";
                    $remove = '#remove';
                    ?>
                    <div class="<?php echo $classes; ?>">
                        <div class="repeater-controls">
                            <?php if(!$this->hide['contract']) : ?>
                            <div class="collapse tr-control-icon tr-control-icon-collapse"></div>
                            <?php endif; ?>
                            <?php if(!$this->hide['move']) : ?>
                            <div class="move tr-control-icon tr-control-icon-move"></div>
                            <?php endif; ?>
                            <?php if(!$this->hide['remove']) : ?>
                            <a href="<?php echo $remove; ?>" class="remove tr-control-icon tr-control-icon-remove" title="remove"></a>
                            <?php endif; ?>
                        </div>
                        <div class="repeater-inputs">
                            <?php
                            if (file_exists( $file )) {
                                /** @noinspection PhpIncludeInspection */
                                include( $file );
                            } else {
                                echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> No component file found <code>{$file}</code></div>";
                            }
                            ?>
                        </div>
                    </div>
                    <?php

                    $form->setGroup($root_group);
                    $form->setCurrentField($this);

                }
            }

            $utility->indexBuffer('fields');

            $blocks = $utility->getBuffer('fields');
            $utility->cleanBuffer();

        }

        return trim($blocks);

    }

    /**
     * Get component folder
     *
     * @return null|string
     */
    public function getComponentFolder() {

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
    public function setComponentFolder($folder_name = '') {

        $paths = Config::locate('paths');
        $dir = $paths['components'] . '/' . $folder_name;

        if(file_exists($dir)) {
            $this->componentFolder = $folder_name;
        }

        return $this;
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

}