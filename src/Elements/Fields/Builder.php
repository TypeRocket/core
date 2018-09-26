<?php

namespace TypeRocket\Elements\Fields;

use \TypeRocket\Utility\Buffer;
use \TypeRocket\Core\Config;
use \TypeRocket\Html\Generator;

class Builder extends Matrix
{

    protected $components = [];

    /**
     * Get the scripts
     */
    public function enqueueScripts() {
        $this->paths = Config::getPaths();
        $assetVersion = Config::locate('app.assets', '1.0');
        $assets = $this->paths['urls']['assets'];
        wp_enqueue_script( 'jquery-ui-sortable', [ 'jquery' ], $assetVersion, true );
        wp_enqueue_script( 'jquery-ui-datepicker', [ 'jquery' ], $assetVersion, true );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_script( 'typerocket-editor', $assets . '/typerocket/js/redactor.min.js', ['jquery'], $assetVersion, true );
    }

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->mxid = md5( microtime( true ) );
        $this->setType( 'builder' );
    }

    /**
     * Get the string
     *
     * @return mixed
     */
    public function getString()
    {
        // enqueue tinymce
        echo '<div style="display: none; visibility: hidden;">';
        wp_editor('', 'tr_dummy_editor');
        echo '</div>';

        $this->setAttribute('name', $this->getNameAttributeString() );
        $buffer = new Buffer();
        $buffer->startBuffer();
        $blocks = $this->getBuilderBlocks();
        $settings = $this->getSettings();
        $count = 0;
        $generator = new Generator();
        $component_name = $this->getComponentFolder();
        $default_null = $generator->newInput('hidden', $this->getAttribute('name'), null)->getString();
	    // add button settings
	    if (isset( $settings['add_button'] )) {
		    $add_button_value = $settings['add_button'];
	    } else {
		    $add_button_value = "Add New";
	    }
        ?>

        <div class="tr-builder">
            <div><?php echo $default_null; ?></div>
            <div class="controls">
                <div class="select">
                    <input type="button" value="<?php echo esc_attr($add_button_value); ?>" class="button tr-builder-add-button">
                    <?php echo $this->getSelectHtml(); ?>
                </div>
                <ul data-thumbnails="<?php echo $this->paths['urls']['components']; ?>" class="tr-components" data-id="<?php echo $this->mxid; ?>" id="components-<?php echo $this->mxid; ?>">
                    <?php foreach($this->components as $option):
                        $count++;
                        $type = $option[0];
                        $name = $option[1];
                        $classes = '';
                        if ($count == 1) {
                            $classes .= ' active';
                        }
                        $thumbnail = $this->getComponentThumbnail($component_name, $type);
                        ?>
                    <li class="tr-builder-component-control <?php echo $classes; ?>">
                        <?php if ($thumbnail) : ?>
                        <img src="<?php echo $thumbnail; ?>" alt="Thumbnail, <?php echo $name; ?>">
                        <?php endif; ?>
                        <span class="tr-builder-component-title"><?php echo $name; ?></span>
                        <span class="remove tr-remove-builder-component"></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="tr-frame-fields" data-id="<?php echo $this->mxid; ?>"  id="frame-<?php echo $this->mxid; ?>">
                <?php echo $blocks; ?>
            </div>

        </div>

        <?php
        $buffer->indexBuffer('main');
        return $buffer->getBuffer('main');
    }

    /**
     * Get select drop down
     *
     * @return string
     */
    private function getSelectHtml()
    {

        $name = $this->getName();
        $folder = $this->getComponentFolder();
        $options = $this->getOptions();
        $options = $options ? $options : $this->setOptionsFromFolder()->getOptions();

        if ($options) {
            $generator = new Generator();
            $generator->newElement( 'ul', array(
                'data-mxid' => $this->mxid,
                'class' => "tr-builder-select builder-select-{$name}",
                'data-group' => $this->getGroup()
            ) );

            foreach ($options as $name => $value) {

                $attr['data-value'] = $value;
                $attr['data-thumbnail'] = $this->getComponentThumbnail($folder, $value);
                $attr['class'] = 'builder-select-option';
                $attr['data-id'] = $this->mxid;
                $attr['data-folder'] = $folder;
                $attr['data-root'] = esc_url( home_url('/', is_ssl() ? 'https' : 'http') );
                $attr['data-group'] = $this->getName();

                $img = new Generator();
                $img->newImage($attr['data-thumbnail']);

                $li = new Generator();
                $li->newElement('li', $attr, '<span>' . $name . '</span>')->appendInside( $img );

                $generator->appendInside( $li );
            }

            $select = $generator->getString();

        } else {
            $dir = $this->paths['components'] . '/' . $folder;
            $select = "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> Add a component folder at <code>{$dir}</code> and add your component files to it.</div>";
        }

        return $select;

    }

    /**
     * Get the component thumbnail
     *
     * @param $name
     * @param $type
     *
     * @return string
     */
    private function getComponentThumbnail($name, $type) {
        $path = '/' .$name . '/' . $type . '.png';
        $thumbnail = $this->paths['urls']['components'] . $path;
        return $thumbnail;
    }

    /**
     * Get the block
     *
     * @return string
     */
    private function getBuilderBlocks()
    {

        $val = $this->getValue();
        $utility = new Buffer();
        $blocks = '';
        $form = $this->getForm();
        $paths = $this->paths;
        $folder = $this->getComponentFolder();

        if (is_array( $val )) {

            $utility->startBuffer();
            $count = 0;
            foreach ($val as $tr_matrix_key => $data) {
                foreach ($data as $tr_matrix_type => $fields) {
                    $count++;
                    $tr_matrix_group = $this->getName();
                    $tr_matrix_type  = $block_name = lcfirst( $tr_matrix_type );
                    $root_group      = $this->getGroup();
                    $form->setDebugStatus(false);
                    $append_group = $root_group;

                    if($root_group) {
                        $append_group .= '.';
                    }

                    $form->setGroup($append_group . "{$tr_matrix_group}.{$tr_matrix_key}.{$tr_matrix_type}");
                    $file        = $paths['components'] . "/" . $folder . "/{$tr_matrix_type}.php";
                    $classes = "builder-field-group builder-type-{$tr_matrix_type} builder-group-{$tr_matrix_group}";

                    if(file_exists($file)) {
                        $line = fgets(fopen( $file, 'r'));
                        if( preg_match("/<[h|H]\\d>(.*)<\\/[h|H]\\d>/U", $line, $matches) ) {
                            $block_name = strip_tags($matches[1]);
                        }
                    }

                    $this->components[] = [$tr_matrix_type, $block_name];

                    if($count == 1) {
                        $classes .= ' active';
                    }

                    ?>
                    <div class="<?php echo $classes; ?>">
                        <div class="builder-inputs">
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
     * Control Add Button
     *
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setControlAdd( $value ) {
		return $this->setSetting('add_button', $value);
	}

}