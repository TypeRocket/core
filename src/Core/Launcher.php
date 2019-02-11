<?php
namespace TypeRocket\Core;

use TypeRocket\Http\Cookie;
use TypeRocket\Http\Rewrites\Builder;
use TypeRocket\Http\Rewrites\Matrix;
use TypeRocket\Http\Rewrites\Rest;
use TypeRocket\Elements\Notice;
use TypeRocket\Http\Routes;
use TypeRocket\Http\SSL;
use TypeRocket\Register\Registry;

class Launcher
{
    public $typerocket = [];

    /**
     * Core Init
     */
    public function initCore()
    {
        $this->typerocket = Config::locate('typerocket');
        $this->initHooks();
        $this->loadPlugins();
        $this->loadResponders();
        $this->initFrontEnd();

        /*
        |--------------------------------------------------------------------------
        | Run Registry
        |--------------------------------------------------------------------------
        |
        | Runs after hooks muplugins_loaded, plugins_loaded and setup_theme
        | This allows the registry to work outside of the themes folder. Use
        | the typerocket_loaded hook to access TypeRocket from your WP plugins.
        |
        */
        add_action( 'after_setup_theme', function () {
            do_action( 'typerocket_loaded' );
            Registry::initHooks();
        } );

        /*
        |--------------------------------------------------------------------------
        | Router
        |--------------------------------------------------------------------------
        |
        | Load TypeRocket Router
        |
        */
	    do_action( 'tr_load_routes' );
        $base_dir = Config::locate('paths.base');
        $routeFile = $base_dir . '/routes.php';
        if( file_exists($routeFile) ) {
            /** @noinspection PhpIncludeInspection */
            require( $routeFile );
        }
        (new Routes())->detectRoute()->initHooks();

        $this->initEndpoints();

        return $this;
    }

    /**
     * Admin Init
     */
    private function initHooks()
    {
        $features = Config::locate('app.features');
        $useContent = function($user) {
            echo '<div class="typerocket-container typerocket-wp-style-guide">';
            do_action( 'tr_user_profile', $user );
            echo '</div>';
        };

        if(!empty($this->typerocket['admin']['post_messages'])) {
            add_action( 'post_updated_messages', [$this, 'setMessages']);
            add_action( 'bulk_post_updated_messages', [$this, 'setBulkMessages'], 10, 2);
        }

        if(isset($features['gutenberg']) && !$features['gutenberg']) {
            add_filter( 'use_block_editor_for_post_type', '__return_false' );
            add_action( 'wp_enqueue_scripts', function() {
                wp_dequeue_style( 'wp-block-library' );
            }, 100 );
        }

        if(isset($features['comments']) && !$features['comments']) {
            include __DIR__ . '/../../features/disable-comments.php';
        }

        if(isset($features['posts_menu']) && !$features['posts_menu']) {
            add_action( 'admin_menu', function() {
                remove_menu_page( 'edit.php' );
            });
            add_action( 'admin_bar_menu', function() {
                global $wp_admin_bar;
                $wp_admin_bar->remove_node( 'new-post' );
            }, 999 );
        }

        add_action( 'edit_user_profile', $useContent );
        add_action( 'show_user_profile', $useContent );
        add_action( 'admin_init', [$this, 'addCss']);
        add_action( 'admin_init', [$this, 'addJs']);
        add_action( 'admin_init', [$this, 'restrictUploadMimeTypes'] );
        add_action( 'admin_footer', [$this, 'addBottomJs']);
        add_action( 'admin_head', [$this, 'addTopJs']);
        add_action( 'admin_notices', [$this, 'setFlash']);
    }

    public function overrideTemplates()
    {
        $paths = Config::locate('paths');
        define( 'WP_DEFAULT_THEME', Config::locate('app.root.theme') );
        register_theme_directory( $paths['themes'] );

        // Set Directories
        add_filter('template_directory', function() use ( $paths ) {
            return $paths['themes'] . '/' . WP_DEFAULT_THEME;
        } );

        add_filter('stylesheet_directory', function() use ( $paths ) {
            return $paths['themes'] . '/' . WP_DEFAULT_THEME;
        } );

        // Set URIs
        add_filter('template_directory_uri', function() use ( $paths ) {
            return $paths['urls']['assets'] . '/' . WP_DEFAULT_THEME;
        });

        add_filter('stylesheet_directory_uri', function() use ( $paths ) {
            return $paths['urls']['assets'] . '/' . WP_DEFAULT_THEME;
        });

        add_filter('stylesheet_uri', function() use ( $paths ) {
            return $paths['urls']['assets'] . '/' . WP_DEFAULT_THEME . '/css/theme.css';
        });

        add_filter('theme_root_uri', function() use ( $paths ) {
            return $paths['urls']['assets'];
        });
    }

    /**
     * Front End Init
     */
    public function initFrontEnd()
    {
        if ( !tr_is_frontend() || !Config::locate('typerocket.frontend.assets') ) {
            return;
        }

        add_action( 'wp_enqueue_scripts', [ $this, 'addCss' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'addJs' ] );
        add_action( 'wp_footer', [ $this, 'addBottomJs' ] );
        add_action( 'wp_head', [ $this, 'addTopJs' ] );
    }

    /**
     * Load plugins
     */
    public function loadPlugins()
    {
        if ( $plugins = Config::locate('app.plugins') ) {
            $plugins_list = apply_filters('tr_set_plugins', $plugins);
            $plugin_dir = Config::locate('paths.plugins');

            foreach ($plugins_list as $plugin) {
                $folder = $plugin_dir . '/' . $plugin . '/';

                if (file_exists($folder . 'init.php')) {
                    /** @noinspection PhpIncludeInspection */
                    include $folder . 'init.php';
                }
            }
        }
    }

    /**
     * Init Responders
     *
     * Add hook into WordPress to give the main functionality needed for
     * TypeRocket to work.
     */
    private function loadResponders() {
        if( defined('WP_INSTALLING') && WP_INSTALLING) {
            return;
        }

        add_action( 'save_post', 'TypeRocket\Http\Responders\Hook::posts' );
        add_action( 'delete_post', 'TypeRocket\Http\Responders\Hook::posts' );
        add_action( 'wp_insert_comment', 'TypeRocket\Http\Responders\Hook::comments' );
        add_action( 'edit_comment', 'TypeRocket\Http\Responders\Hook::comments' );
        add_action( 'edit_term', 'TypeRocket\Http\Responders\Hook::taxonomies', 10, 4 );
        add_action( 'create_term', 'TypeRocket\Http\Responders\Hook::taxonomies', 10, 4 );
        add_action( 'profile_update', 'TypeRocket\Http\Responders\Hook::users' );
        add_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );
    }

    /**
     * Set custom post type messages
     *
     * @param $messages
     *
     * @return mixed
     */
    public function setMessages( $messages )
    {
        global $post;

        $pt = get_post_type( $post->ID );

        if ($pt != 'attachment' && $pt != 'page' && $pt != 'post') :

            $obj      = get_post_type_object( $pt );
            $singular = $obj->labels->singular_name;

            if ($obj->public == true) :
                /** @noinspection HtmlUnknownTarget */
                $view    = sprintf( __( '<a href="%s">View %s</a>' ), esc_url( get_permalink( $post->ID ) ), $singular );
                $preview = sprintf( __( '<a target="_blank" href="%s">Preview %s</a>' ),
                    esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ), $singular );
            else :
                $view = $preview = '';
            endif;

            $messages[$pt] = [
                1  => sprintf( __( '%s updated. %s' ), $singular, $view ),
                2  => __( 'Custom field updated.' ),
                3  => __( 'Custom field deleted.' ),
                4  => sprintf( __( '%s updated.' ), $singular ),
                5  => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s' ), $singular,
                    wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
                6  => sprintf( __( '%s published. %s' ), $singular, $view ),
                7  => sprintf( __( '%s saved.' ), $singular ),
                8  => sprintf( __( '%s submitted. %s' ), $singular, $preview ),
                9  => sprintf( __( '%s scheduled for: <strong>%1$s</strong>. %s' ), $singular,
                    date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ), $preview ),
                10 => sprintf( __( '%s draft updated. ' ), $singular ),
            ];

        endif;

        return $messages;
    }

    /**
     * Set custom post type bulk messages to make more since.
     *
     * @param $bulk_messages
     * @param $bulk_counts
     *
     * @return mixed
     */
    public function setBulkMessages($bulk_messages, $bulk_counts)
    {
            global $post;
            if(empty($post)) { return $bulk_messages; }

            $pt = get_post_type( $post->ID );

            if ($pt != 'attachment' && $pt != 'page' && $pt != 'post') :
                $obj      = get_post_type_object( $pt );
                $singular = strtolower($obj->labels->singular_name);
                $plural   = strtolower($obj->labels->name);

                $bulk_messages[$pt] = array(
                    'updated'   => _n( "%s {$singular} updated.", "%s {$plural} updated.", $bulk_counts["updated"] ),
                    'locked'    => _n( "%s {$singular} not updated, somebody is editing it.", "%s {$plural} not updated, somebody is editing them.", $bulk_counts["locked"] ),
                    'deleted'   => _n( "%s {$singular} permanently deleted.", "%s {$plural} permanently deleted.", $bulk_counts["deleted"] ),
                    'trashed'   => _n( "%s {$singular} moved to the Trash.", "%s {$plural} moved to the Trash.", $bulk_counts["trashed"] ),
                    'untrashed' => _n( "%s {$singular} restored from the Trash.", "%s {$plural} restored from the Trash.", $bulk_counts["untrashed"] ),
                );
            endif;

            return $bulk_messages;
    }

    /**
     *  Set flashing for admin notices
     */
    public function setFlash() {
        if( !empty($_COOKIE['tr_admin_flash']) ) {
            $cookie = new Cookie();
            $data = $cookie->getTransient('tr_admin_flash');
            Notice::dismissible($data);
        }
    }

    /**
     * Add CSS
     */
    public function addCss()
    {
        $paths = Config::locate('paths');
        $assetVersion = Config::locate('app.assets');
	    $assets = SSL::fixSSLUrl($paths['urls']['assets']);

        wp_enqueue_style( 'typerocket-styles', $assets . '/typerocket/css/core.css', [], $assetVersion);
        wp_enqueue_style( 'typerocket-styles-redactor', $assets . '/typerocket/css/redactor.css', [], $assetVersion);

        if (is_admin()) {
            wp_enqueue_style( 'wp-color-picker' );
        }
    }

    /**
     * Add JavaScript
     */
    public function addJs()
    {
        $paths = Config::locate('paths');
        $assetVersion = Config::locate('app.assets');
        $assets = SSL::fixSSLUrl($paths['urls']['assets']);

        wp_enqueue_script( 'typerocket-scripts-global', $assets . '/typerocket/js/global.js', [], $assetVersion );
    }

    /**
     * restrict upload mime types
     * https://wordpress.stackexchange.com/a/97025
     * https://wordpress.stackexchange.com/a/174805
     */
    public function restrictUploadMimeTypes() {
        add_filter( 'wp_handle_upload_prefilter', function ( $file ) {
            if ( empty( $_POST['allowed_mime_types'] ) || empty( $file['type'] ) ) {
                return $file;
            }
            $allowed_mime_types = explode( ',', $_POST['allowed_mime_types'] );
            if ( in_array( $file['type'], $allowed_mime_types ) ) {
                return $file;
            }
            // Cater for "group" allowed mime types eg "image", "audio" etc. to match
            // files of type "image/png", "audio/mp3" etc.
            if ( ( $slash_pos = strpos( $file['type'], '/' ) ) > 0 ) {
                if ( in_array( substr( $file['type'], 0, $slash_pos ), $allowed_mime_types ) ) {
                    return $file;
                }
            }
            $file['error'] = __( 'Sorry, you cannot upload this file type for this field.' );
            return $file;
        } );
    }

    /**
     * Add JavaScript to very bottom
     *
     * This is in place so that all other scripts from fields come
     * before the main typerocket script.
     */
    public function addBottomJs()
    {
        $paths = Config::locate('paths');
        $assetVersion = Config::locate('app.assets');
	    $assets = SSL::fixSSLUrl($paths['urls']['assets']);

        wp_enqueue_script( 'typerocket-scripts', $assets . '/typerocket/js/core.js', [ 'jquery' ], $assetVersion, true );
    }

    public function addTopJs()
    {
        ?>
        <script>window.trHelpers = {site_uri: "<?php echo esc_url(home_url());?>"}</script>
        <?php
        wp_localize_script( 'typerocket-scripts', 'trHelpers', [ 'site_uri' => home_url() ] );
    }

    /**
     * Endpoints Init
     */
    public function initEndpoints()
    {
        add_action('init', [$this, 'addRewrites']);

        add_filter( 'query_vars', function($vars) {
            $vars[] = 'tr_json_controller';
            $vars[] = 'tr_json_item';
            $vars[] = 'tr_matrix_group';
            $vars[] = 'tr_matrix_folder';
            $vars[] = 'tr_matrix_type';
            $vars[] = 'tr_builder_group';
            $vars[] = 'tr_builder_folder';
            $vars[] = 'tr_builder_type';
            $vars[] = 'tr_route_var';

            return $vars;
        } );

        add_filter( 'template_include', function($template) {

            $resource = get_query_var('tr_json_controller', null);

            $load_template = ($resource);
            $load_template = apply_filters('tr_json_api_template', $load_template);

            if($load_template) {
                new Rest();
            }

            return $template;
        }, 99 );

        add_filter( 'template_include', function($template) {

            $matrix_group = get_query_var('tr_matrix_group', null);
            $matrix_type = get_query_var('tr_matrix_type', null);
            $matrix_folder = get_query_var('tr_matrix_folder', null);

            $load_template = ($matrix_group && $matrix_type && $matrix_folder);
            $load_template = apply_filters('tr_matrix_api_template', $load_template);

            if($load_template) {
                new Matrix();
                die();
            }

            return $template;
        }, 99 );

        add_filter( 'template_include', function($template) {

            $builder_group = get_query_var('tr_builder_group', null);
            $builder_type = get_query_var('tr_builder_type', null);
            $builder_folder = get_query_var('tr_builder_folder', null);

            $load_template = ($builder_group && $builder_type && $builder_folder );
            $load_template = apply_filters('tr_builder_api_template', $load_template);

            if($load_template) {
                new Builder();
                die();
            }

            return $template;
        }, 99 );

        add_action( 'rest_api_init', function () {
            register_rest_route( 'typerocket/v1', '/search', [
                'methods' => 'GET',
                'callback' => '\\TypeRocket\\Http\\Rewrites\\WpRestApi::search',
                'permission_callback' => '\\TypeRocket\\Http\\Rewrites\\WpRestApi::permission'
            ]);
        } );
    }

    /**
     * Add Rewrite rules
     */
    public function addRewrites()
    {
        $regex = 'tr_json_api/v1/([^/]*)/?$';
        $location = 'index.php?tr_json_controller=$matches[1]';
        add_rewrite_rule( $regex, $location, 'top' );

        $regex = 'tr_json_api/v1/([^/]*)/([^/]*)/?$';
        $location = 'index.php?tr_json_controller=$matches[1]&tr_json_item=$matches[2]';
        add_rewrite_rule( $regex, $location, 'top' );

        // Matrix API
        $regex = 'tr_matrix_api/v1/([^/]*)/([^/]*)/([^/]*)/?$';
        $location = 'index.php?tr_matrix_group=$matches[1]&tr_matrix_type=$matches[2]&tr_matrix_folder=$matches[3]';
        add_rewrite_rule( $regex, $location, 'top' );

        // Builder API
        $regex = 'tr_builder_api/v1/([^/]*)/([^/]*)/([^/]*)/?$';
        $location = 'index.php?tr_builder_group=$matches[1]&tr_builder_type=$matches[2]&tr_builder_folder=$matches[3]';
        add_rewrite_rule( $regex, $location, 'top' );
    }

}
