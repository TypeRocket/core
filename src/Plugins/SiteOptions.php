<?php
namespace TypeRocket\Plugins
{
    class SiteOptions
    {
        public $name = 'site_options';
        public $icon = 'envira';
        public $capability = 'manage_options';
        public $use_menu = true;
        public $use_simple_nav = true;

        public function __construct()
        {
            if ( ! function_exists( 'add_action' )) {
                echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
                exit;
            }

            do_action('trp_site_options', $this);
            add_action( 'typerocket_loaded', [$this, 'setup']);
        }

        public function setup()
        {
            $options = [
                'capability'=> $this->capability,
                'menu'=> 'Site Options',
                'view_file' => apply_filters('trp_site_options_page', __DIR__ . '/views/so-page.php'),
                'position' => 60
            ];

            $menu = [
                'capability'=> $this->capability,
                'view_file' => apply_filters( 'trp_site_options_menu_page', __DIR__ . '/views/so-menu.php'),
                'position' => 60
            ];

            $page_options = tr_page('Site', 'options', 'Site Options', $options)
                ->setIcon($this->icon);

            if($this->use_menu) {
                $page_options->addPage(
                    tr_page('Site', 'menu', 'Navigation', $menu)
                );
            }

            add_action( 'wp_before_admin_bar_render', [$this, 'admin_bar_menu'], 100 );
            add_action( 'admin_menu', array( $this, 'admin_menu' ), 999 );
        }

        public function admin_menu()
        {
            global $pagenow, $plugin_page, $submenu;

            if($this->use_simple_nav) {
                // Remove Submenus
                remove_menu_page('themes.php');
                // unset($submenu['options-general.php']);
                // remove_menu_page('options-general.php');
                unset($submenu['upload.php']);
                remove_menu_page('upload.php');
                // remove_menu_page('tools.php');
                remove_menu_page('options-permalink.php');
                unset($submenu['plugins.php']);
                remove_menu_page('plugins.php');

                // Add Back Submenus - Only needed if there is no sub tr_page for the main page
                // add_submenu_page('site_options', 'Site Options', 'Site Options', 'manage_options', 'site_options');
                add_submenu_page('site_options', 'URLs', 'URLs', $this->capability, 'options-permalink.php');
                add_submenu_page('site_options', 'Plugins', 'Plugins', $this->capability, 'plugins.php');
                add_submenu_page('site_options', 'Media', 'Media', $this->capability, 'upload.php');

                if(in_array($pagenow, ['options-permalink.php', 'plugins.php', 'upload.php'])) {
                    add_filter('parent_file', function($v) { return 'site_options'; }, 9999);
                    add_filter('submenu_file',function($v) use ($pagenow) { return $pagenow;  }, 9999);
                } elseif($plugin_page == 'site_options') {
                    add_filter('parent_file', function($v) { return 'site_options'; }, 9999);
                    add_filter('submenu_file',function($v) { return 'site_options'; }, 9999);
                }
            }
        }

        public function add_sub_menu( $name, $link, $root_menu, $id, $meta = false )
        {
            /** @var \WP_Admin_Bar $wp_admin_bar */
            global $wp_admin_bar;
            if ( ! current_user_can( $this->capability ) || ! is_admin_bar_showing()) {
                return;
            }

            $wp_admin_bar->add_menu( [
                'parent' => $root_menu,
                'id'     => $id,
                'title'  => $name,
                'href'   => $link,
                'meta'   => $meta
            ]);
        }

        public function admin_bar_menu()
        {
            $this->add_sub_menu("Site Options", admin_url() . 'admin.php?page=site_options', "site-name",
                "tr-site-options" );
        }
    }

}

namespace
{
    function tr_link_fields(\TypeRocket\Elements\Form $form) {
        return [
            $form->text('Text'),
            $form->text('URL', ['placeholder' => 'https://me.com, /contact, or #page-section']),
            $form->toggle('Tab')->setText('Open in tab')
        ];
    }
}
