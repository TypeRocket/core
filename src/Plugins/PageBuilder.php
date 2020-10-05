<?php
namespace TypeRocket\Plugins;

class PageBuilder
{
    public $post_types = ['page'];
    public $show_status = true;

    function __construct()
    {
        $this->post_types = apply_filters('tr_builder_post_types', ['page']);

        add_action('edit_form_after_title', [$this, 'edit_form_after_title'], 9999999999999);
        add_action('edit_form_after_editor', [$this, 'edit_form_after_editor'], 0);
        do_action('tr_builder_plugin_init', $this);

        if($this->show_status) {
            add_filter( 'display_post_states', function($post_states, $post ) {
                $meta = get_post_meta($post->ID, 'use_builder', true);
                if($meta == '1') {
                    $post_states['builder'] = 'Builder Page';
                }
                return $post_states;
            }, 0, 2 );
        }
    }

    function edit_form_after_title($post)
    {
        if (is_array($this->post_types) && in_array($post->post_type, $this->post_types)) :

            $form = tr_form();

            $builder_active = $editor_active = '';

            $page_boxes = tr_posts_field("builder");
            $use_builder = tr_posts_field("use_builder");
            $is_not_set = (!isset($use_builder) || $use_builder === "");
            $has_boxes = is_array($page_boxes);
            $hide_builder = $hide_editor = '';

            if ($use_builder == '1' || ($has_boxes && $is_not_set)) {
                $builder_active = 'builder-active button-primary ';
                $hide_editor = 'style="display: none;"';
            } else {
                $editor_active = 'builder-active button-primary ';
                $hide_builder = 'style="display: none;"';
            }

            echo '<div id="tr_page_type_toggle"><div><a id="tr_page_builder_control" href="#tr_page_builder" class="button ' . $builder_active . '">Builder</a><a href="#builderStandardEditor" class="button ' . $editor_active . '">' . __('Standard Editor') . '</a></div></div>';

            echo '<div id="builderSelectRadio">';
            echo $form->checkbox(__('Use Builder'));
            echo '</div>';

            echo '<div id="tr_page_builder" ' . $hide_builder . ' class="typerocket-container typerocket-dev">';
            do_action('tr_before_builder_field', $this, $form, $use_builder);
            echo apply_filters('tr_page_builder_field', $form->builder('Builder'));
            do_action('tr_after_builder_field', $this, $form, $use_builder);
            echo '</div><div id="builderStandardEditor" ' . $hide_editor . '>';

        endif;
    }

    function edit_form_after_editor($post)
    {
        if (is_array($this->post_types) && in_array($post->post_type, $this->post_types)) :
            echo '</div>';
        endif;
    }

}