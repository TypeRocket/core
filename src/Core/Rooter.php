<?php
namespace TypeRocket\Core;

use TypeRocket\Database\Query;
use TypeRocket\Utility\Str;
use TypeRocket\Core\Config;

class Rooter
{
    /**
     * Noted Hooks
     *
     * Might use these in the future to remove the need for
     * the resources/themes parent folder.
     *
     * filters:
     *
     * wp_cache_themes_persistently
     * theme_root
     */
    public function boot()
    {
        do_action('typerocket_rooter');

        if(!Config::get('app.root.themes.override', true)) {
            return;
        }

        $paths = Config::get('paths');
        $urls = Config::get('urls');
        $root = Config::get('app.root.themes');
        $themes = [
            $paths['themes'],
            WP_CONTENT_DIR . '/themes',
        ];

        apply_filters('typerocket_rooter_wp_themes', $themes);

        foreach ($themes as $loc) {
            register_theme_directory( $loc );
        }

        new \WP_Theme($root['theme'], $paths['themes']);

        if(defined('TYPEROCKET_ROOT_INSTALL')) {
            define( 'WP_DEFAULT_THEME', $root['theme'] );
        }

        if($root['flush']) {
            add_filter( "option_stylesheet_root", function($v, $option) use ($paths) {
                global $wp_theme_directories, $wpdb;

                if(!in_array($v, $wp_theme_directories) && !in_array(WP_CONTENT_DIR . $v, $wp_theme_directories)) {
                    Query::new()->table($wpdb->options)->where('option_name', 'stylesheet_root')->update([
                        'option_value' => $paths['themes']
                    ]);

                    return $paths['themes'];
                }

                return $v;
            }, 10, 2);
        }

        add_filter('stylesheet_uri', function(...$args) use ( $urls, $root ) {
            if(Str::starts($urls['assets'], $args[0])) {
                return $args[1] . '/' . ($root['stylesheet'] ?? '/theme/theme.css');
            }

            return $args[0];
        }, 10, 3);

        add_filter('theme_root_uri', function(...$args) use ( $paths, $urls ) {
            if(Str::starts($paths['themes'], $args[0])) {
                return $urls['assets'];
            }

            return $args[0];
        }, 10, 3);
    }
}