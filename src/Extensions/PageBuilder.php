<?php
namespace TypeRocket\Extensions;

use App\Elements\Form;
use TypeRocket\Core\Config;
use TypeRocket\Elements\Traits\OptionsTrait;
use TypeRocket\Http\Request;
use TypeRocket\Register\MetaBox;
use TypeRocket\Utility\ModelField;
use TypeRocket\Utility\RuntimeCache;

class PageBuilder
{
    use OptionsTrait;

    protected $postTypes = ['page'];
    protected $fieldName = null;
    public const FIELD_NAME = 'builder';
    protected $showStatus = true;

    public function __construct($post_types = ['page'], $field_name = null)
    {
        if(!Config::env('TYPEROCKET_PAGE_BUILDER', true)) {
            return;
        }

        $this->postTypes = apply_filters('typerocket_ext_builder_post_types', $post_types);
        $this->fieldName = $field_name ?? $this->fieldName ?? static::FIELD_NAME;
        $this->options = [];

        add_filter('use_block_editor_for_post', [$this, 'gutenberg'], 10, 2);
        add_action('edit_form_after_title', [$this, 'edit_form_after_title'], 9999999999999);
        add_action('edit_form_after_editor', [$this, 'edit_form_after_editor'], 0);

        do_action('typerocket_builder_plugin_init', $this);

        add_action( 'enqueue_block_editor_assets', function() {
            global $post;

            if(isset($post) && !in_array($post->post_type, $this->postTypes)) {
                return;
            }

            $url = Config::get('urls.typerocket');
            $manifest = RuntimeCache::getFromContainer()->get('manifest');

            $url = $url . $manifest['/js/builder.ext.js'];

            wp_enqueue_script(
                'typerocket-builder-main-js',
                $url,
                ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data']
            );

            wp_set_script_translations( 'typerocket-builder-main-js', 'typerocket-domain' );
        });

        if ($this->showStatus) {
            add_filter('display_post_states', function ($post_states, $post) {
                $meta = get_post_meta($post->ID, 'use_builder', true);
                if ($meta == '1') {
                    $post_states['builder'] = 'Builder Page';
                }
                return $post_states;
            }, 0, 2);
        }
    }

    public function gutenberg($value, $post) {

        $use_builder = get_post_meta($post->ID, 'use_builder', true);
        $builder = isset($use_builder) && $use_builder !== 'gutenberg';
        $_get_tr_builder = $_GET['tr_builder'] ?? null;

        if(isset($_get_tr_builder)) {
            update_post_meta($post->ID, 'use_builder', $_get_tr_builder == '1' ?: '0');
            $builder = true;
        }
        elseif($_GET['tr_builder_gutenberg'] ?? null && $value) {
            update_post_meta($post->ID, 'use_builder', 'gutenberg');
            $builder = false;
        }
        elseif($builder) {
            $builder = true;
        }
        elseif($value) {
            update_post_meta($post->ID, 'use_builder', 'gutenberg');
            $builder = false;
        }

        if( !$builder && in_array($post->post_type, $this->postTypes) && $value ) {
            return true;
        }

        if(in_array($post->post_type, $this->postTypes)) {

            MetaBox::add('Editor')->setPriority('high')->setContext('side')->addScreen($post->post_type)->setCallback(function () use ($post, $use_builder, $value) {
                $builder_active = $editor_active = '';

                $page_boxes = ModelField::post($this->fieldName, $post->ID);
                $is_not_set = (!isset($use_builder) || $use_builder === "");
                $has_boxes = is_array($page_boxes);

                if ($use_builder == '1' || ($has_boxes && $is_not_set)) {
                    $builder_active = 'builder-active button-primary ';
                } else {
                    $editor_active = 'builder-active button-primary ';
                }

                $gutenberg_url = Request::new()->getModifiedUri(['tr_builder_gutenberg' => '1', 'tr_builder' => null]);

                echo '<div id="tr_page_type_toggle"><div><a id="tr_page_builder_control" href="#tr_page_builder" class="button ' . $builder_active . '">' . __('Builder', 'typerocket-ext-pb') . '</a><a href="#builderStandardEditor" class="button ' . $editor_active . '">' . __('Standard Editor', 'typerocket-ext-pb') . '</a></div></div>';
                echo '<div id="builderSelectRadio">';
                echo \TypeRocket\Utility\Helper::form()->checkbox('use_builder')->setLabel(__('Use Builder', 'typerocket-ext-pb'));
                echo '</div>';

                if ($value) {
                    echo "<p></p>";
                    echo "<p style='margin: 10px'><i class='dashicons dashicons-external'></i> <a href=\"{$gutenberg_url}\">" . __('Use Gutenberg Editor', 'typerocket-ext-pb') . "</a></p>";
                }
            })->register();

            add_action( 'wp_enqueue_scripts', [$this, 'remove_gutenberg'], 100 );

            return false;
        }

        return $value;
    }

    public function remove_gutenberg()
    {
        wp_dequeue_style( 'wp-block-library' );
    }

    public function edit_form_after_title($post)
    {
        if (is_array($this->postTypes) && in_array($post->post_type, $this->postTypes)) :

            /** @var Form $form */
            $form = \TypeRocket\Utility\Helper::form();

            $page_boxes = ModelField::post($this->fieldName);
            $use_builder = get_post_meta($post->ID, "use_builder", true);
            $is_not_set = (!isset($use_builder) || $use_builder === "");
            $has_boxes = is_array($page_boxes);
            $hide_builder = $hide_editor = '';

            if($use_builder == 'gutenberg') {
                return;
            }

            if ($use_builder == '1' || ($has_boxes && $is_not_set)) {
                $hide_editor = 'style="display: none;"';
            } else {
                $hide_builder = 'style="display: none;"';
            }

            $field = $form->builder($this->fieldName)
                ->setOptions($this->options)
                ->setLabelOption(Config::get('app.debug'))
                ->setLabel(__("Builder", 'typerocket-ext-pb'));

            echo '<div id="tr_page_builder" ' . $hide_builder . ' class="typerocket-container typerocket-dev">';
            do_action('typerocket_before_builder_field', $this, $form, $use_builder);
            echo apply_filters('typerocket_page_builder_field', $field);
            do_action('typerocket_after_builder_field', $this, $form, $use_builder);
            echo '</div><div id="builderStandardEditor" ' . $hide_editor . '>';

        endif;
    }

    public function edit_form_after_editor($post)
    {
        $use_builder = get_post_meta($post->ID, "use_builder", true);

        if($use_builder == 'gutenberg') {
            return;
        }

        if (is_array($this->postTypes) && in_array($post->post_type, $this->postTypes)) :
            echo '</div>';
        endif;
    }
}