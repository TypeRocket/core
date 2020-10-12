<?php
namespace TypeRocket\Core;

use TypeRocket\Utility\Str;

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
        do_action('tr_rooter');

        if(!tr_config('app.root.themes.override', true)) {
            return;
        }

        $paths = tr_config('paths');
        $urls = tr_config('urls');
        $root = tr_config('app.root.themes');

        register_theme_directory( $paths['themes'] );
        register_theme_directory( apply_filters('tr_rooter_wp_themes', WP_CONTENT_DIR . '/themes') );

        new \WP_Theme($root['theme'], $paths['themes']);

        // Set URLs
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