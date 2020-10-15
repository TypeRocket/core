<?php
namespace TypeRocket\Core;

class Pro
{
    public function __construct()
    {
        add_action( 'wp_update_nav_menu_item', 'TypeRocket\Http\Responders\Hook::menus', 10, 3 );
        add_filter('typerocket_repeater_item_controls', [$this, 'addCloneToRepeatable']);
        add_filter('typerocket_component_item_controls', [$this, 'addCloneToRepeatable']);
        add_action('', [$this, 'bottomJs'], 10, 2);
    }

    public function addCloneToRepeatable($list)
    {
        $list['clone'] = [
            'class' => 'tr-repeater-clone tr-control-icon tr-control-icon-clone',
            'title' => __('Duplicate', 'typerocket-domain'),
            'tabindex' => '0'
        ];

        return $list;
    }

    public function bottomJs($url, $manifest)
    {
        $version = 1;
        wp_enqueue_script( 'typerocket-scripts-advanced', $url . '/js/advanced.js?adv_id='.$version, [ 'jquery' ], false, true );
    }
}