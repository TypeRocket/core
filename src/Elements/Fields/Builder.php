<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Template\Component;
use TypeRocket\Html\Html;

class Builder extends Matrix
{
    protected $components = [];
    protected $componentValues = [];

    /**
     * Run on construction
     */
    protected function init()
    {
        parent::init();
        $this->setType( 'builder' );
    }

    /**
     * Get the string
     *
     * @return mixed
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        // enqueue tinymce
        static::dummyEditor();

        $this->setAttribute('name', $this->getNameAttributeString() );

        $blocks = $this->getBuilderBlocks();
        $settings = $this->getSettings();
        $generator = new Html();
        $default_null = $generator->input('hidden', $this->getAttribute('name'), null)->getString();

	    if (isset( $settings['add_button'] )) {
		    $add_button_value = $settings['add_button'];
	    } else {
		    $add_button_value = "Add New";
	    }
        ob_start();
        ?>
        <div class="tr-builder">
            <div class="tr-builder-hidden-field"><?php echo $default_null; ?></div>
            <div class="tr-builder-controls">
                <div class="select">
                    <input type="button" value="<?php echo esc_attr($add_button_value); ?>" class="button tr-builder-add-button">
                    <?php echo $this->getSelectHtml(); ?>
                </div>
                <ul data-thumbnails="<?php echo $this->urls['components']; ?>" class="tr-components">
                    <?php if(!empty($this->components) && is_array($this->components)) :
                        $i = 0;
                        /** @var Component $component */
                        foreach ($this->components as $component)  {
                            static::componentTile($component, $this->getName(), ++$i == 1 ? ' active' : '');
                        }
                        ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="tr-frame-fields">
                <?php echo $blocks; ?>
            </div>

        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Get select drop down
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
            $ul = Html::ul( [
                'class' => "tr-builder-select builder-select-{$name}",
                'data-tr-group' => $this->getGroupWithFrom()]);

            /**
             * @var string $title
             * @var Component $component
             */
            foreach ($options as $title => $component) {
                $attr['data-value'] = $component->registeredAs();
                $attr['tabindex'] = '0';
                $attr['data-thumbnail'] = $component->thumbnail();
                $attr['class'] = 'tr-builder-select-option';
                $attr['data-root'] = esc_url( home_url('/', is_ssl() ? 'https' : 'http') );
                $attr['data-group'] = $this->getName();

                $img = Html::img($attr['data-thumbnail']);
                $li = Html::li($attr, '<span>' . $title . '</span>')->nest( $img );

                $ul->nest( $li );
            }

            $select = $ul->getString();

        } else {
            $select = "<div class=\"tr-dev-alert-helper\"><i class=\"icon dashicons dashicons-editor-code\"></i> Add a <code>{$name}</code> component to the components config filet.</div>";
        }

        return $select;
    }

    /**
     * Get the component thumbnail
     *
     * @param Component|null $component
     * @param string $type
     * @return string
     */
    protected function getComponentThumbnail($component, $type)
    {
        return $component->thumbnail() ?? $this->urls['components'] . '/' . $type . '.png';
    }

    /**
     * Get the block
     *
     * @return string
     */
    protected function getBuilderBlocks()
    {
        $this->setCast('array');
        $val = $this->componentValues = $this->getValue();
        $blocks = '';
        $form = $this->getForm()->clone();

        if (is_array( $val )) {

            ob_start();
            $i = 0;
            foreach ($val as $tr_matrix_key => $data) {
                foreach ($data as $tr_matrix_type => $fields) {
                    $tr_matrix_group = $this->getName();
                    $root_group      = $this->getGroupWithFrom();
                    $form->setDebugStatus(false);
                    $append_group = $root_group;

                    if($root_group) {
                        $append_group .= '.';
                    }

                    $form->setGroup($append_group . "{$tr_matrix_group}.{$tr_matrix_key}.{$tr_matrix_type}");
                    $class = static::getComponentClass($tr_matrix_type, $tr_matrix_group)->form($form)->data($form->getModel());
                    $this->components[] = $class;

                    static::componentTemplate($class, $tr_matrix_group, ++$i == 1 ? 'active' : '');

                    $form->setGroup($root_group);
                }
            }

            $blocks = ob_get_clean();

        }

        return trim($blocks);
    }

    /**
     * @param Component $component
     * @param string $group
     * @param string $classes
     */
    public static function componentTemplate($component, $group, $classes = '')
    {
        $name = $component->feature('nameable');
        $name = apply_filters('typerocket_component_name', $name, 'builder', $component, $group, $classes);
        ?>
        <div data-tr-component="<?php echo $component->uuid(); ?>" class="builder-field-group builder-type-<?php echo esc_attr($component->registeredAs()); ?> builder-group-<?php echo esc_attr($group); ?> <?php echo $classes; ?>">
            <div class="tr-component-inputs tr-builder-inputs">
                <?php
                echo "<h3 class=\"tr-component-group-name\">{$name}</h3>";
                $component->fields();
                ?>
            </div>
            <?php do_action('typerocket_component_include', 'builder', $component, $group); ?>
        </div>
        <?php
    }

    /**
     * Get the component thumbnail
     *
     * @param Component|null $component
     * @param string $group
     * @param string $classes
     *
     * @return string
     */
    public static function componentTile($component, $group, $classes = '')
    {
        ?>
        <li data-tr-component-tile="<?php echo $component->uuid(); ?>" tabindex="0" class="tr-builder-component-control <?php echo $classes; ?>">
            <img src="<?php echo $component->thumbnail(); ?>" alt="Thumbnail, <?php echo $component->title(); ?>">
            <span class="tr-builder-component-title"><?php echo $component->title(); ?></span>
            <a tabindex="0" class="remove tr-remove-builder-component"></a>
            <?php echo $component->feature('cloneable') ?>
        </li>
        <?php
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