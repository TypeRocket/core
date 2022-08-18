<?php
namespace TypeRocket\Core;

use Composer\InstalledVersions;
use TypeRocket\Controllers\FieldsController;
use TypeRocket\Controllers\RestController;
use TypeRocket\Elements\BaseForm;
use TypeRocket\Http\Cookie;
use TypeRocket\Http\ErrorCollection;
use TypeRocket\Http\Redirect;
use TypeRocket\Http\Request;
use TypeRocket\Elements\Notice;
use TypeRocket\Http\Response;
use TypeRocket\Http\Route;
use TypeRocket\Http\RouteCollection;
use TypeRocket\Http\Router;
use TypeRocket\Http\SSL;
use TypeRocket\Register\Registry;
use TypeRocket\Services\Service;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\RuntimeCache;
use TypeRocket\Utility\Str;

class System
{
    public const ALIAS = 'system';
    public const ADVANCED = 'TypeRocketPro\Core\AdvancedSystem';
    public const STATE = '_typerocket_site_state_changed';

    protected $stash = [];
    protected $loaded = false;
    protected $frontend_mode = false;

    protected array $loadedExtensions = [];
    protected array $loadedServices = [];

    /**
     * Boot Core
     */
    public function boot()
    {
        if($this->loaded) {
            return $this;
        }

        $this->loaded = true;

        /*
        |--------------------------------------------------------------------------
        | Register System
        |--------------------------------------------------------------------------
        |
        | Register system into the DI container.
        |
        */
        Container::singleton(self::class, function() {
            return $this;
        }, static::ALIAS);

        /*
        |--------------------------------------------------------------------------
        | Load TypeRocket
        |--------------------------------------------------------------------------
        |
        | Use the typerocket_loaded and typerocket_before_load hooks to access
        | TypeRocket from your WP plugins and themes.
        |
        */
        add_action('after_setup_theme', function() {
            do_action('typerocket_before_load', $this);
            $this->loadRuntimeCacheValues();
            $this->maybeLoadAdvancedSystem();
            $this->loadServices();
            $this->loadExtensions();
            $this->initHooks();
            $this->loadResponders();
            $this->maybeFrontend();
            do_action('typerocket_loaded', $this);
            Registry::initHooks();
        }, 20);

        /*
        |--------------------------------------------------------------------------
        | Router
        |--------------------------------------------------------------------------
        |
        | Load TypeRocket router through the typerocket_loaded action, so it can
        | be unregistered if desired.
        |
        */
        add_action('typerocket_loaded', static::class."::loadRoutes", 11);

        /*
        |--------------------------------------------------------------------------
        | Site Health
        |--------------------------------------------------------------------------
        |
        | Add TypeRocket Information to the WordPress Site Health page. Located at
        | the URL of /wp-admin/site-health.php?tab=debug in the WordPress admin.
        |
        */
        add_action('debug_information', [$this, 'health']);

        return $this;
    }

    /**
     * Maybe Load TypeRocket Pro Advanced System
     */
    public function maybeLoadAdvancedSystem() {
        if(class_exists(static::ADVANCED)) {
            (new Resolver())->resolve(static::ADVANCED);
        }

        return $this;
    }

    public function loadRuntimeCacheValues()
    {
        $assets = Config::get('paths.assets');
        $manifest = json_decode(file_get_contents($assets . '/typerocket/mix-manifest.json'), true);

        $cache = RuntimeCache::getFromContainer();
        $cache->update('manifest', $manifest);

        if( !empty($_COOKIE[ErrorCollection::KEY]) ) {
            $cache->update(ErrorCollection::KEY, ErrorCollection::new() );
        }

        $url = SSL::fixSSLUrl(Config::get('urls.typerocket'));

        $this->stash['url.typerocket'] = $url;
        $this->stash['manifest.typerocket'] = $manifest;
    }

    /**
     * Load Routes
     */
    public static function loadRoutes()
    {
        if(!Config::env('TYPEROCKET_ROUTES', true)) {
            return;
        }

        Container::singleton(RouteCollection::class, function() {
            return new RouteCollection();
        }, RouteCollection::ALIAS);

        do_action('typerocket_routes');
        static::addRewrites();
        $public_routes = Config::get('paths.routes') . '/public.php';
        if( file_exists($public_routes) ) {
            /** @noinspection PhpIncludeInspection */
            require( $public_routes );
        }
        do_action('typerocket_after_routes');
        /** @var RouteCollection $routes */
        $routes = RouteCollection::getFromContainer();
        $request = new Request;
        $config = apply_filters('typerocket_router_config', ['root' => null]);

        (new Router($request, $config, $routes))->detectRoute()->initHooks();
    }

    /**
     * Admin Init
     */
    public function initAdminHooks()
    {
        $this->addCss();
        $this->addJs();

        add_action( 'edit_user_profile', [$this, 'userProfiles'] );
        add_action( 'show_user_profile', [$this, 'userProfiles'] );
        add_action( 'wp_nav_menu_item_custom_fields', [$this, 'menuFields'], 10, 5 );
        add_action( 'admin_head', [$this, 'addTopJs']);
        add_action( 'admin_footer', [$this, 'addBottomJs']);
        add_action( 'admin_notices', [$this, 'setFlash']);
        add_filter( 'wp_handle_upload_prefilter', [$this, 'restrictUploadMimeTypes'] );
    }

    /**
     * Admin Init
     */
    public function initHooks()
    {
        add_action( 'wp_loaded', [$this, 'checkSiteStateChanged']);
        add_action( 'admin_init', [$this, 'initAdminHooks'] );
        add_filter( 'wp_handle_upload_prefilter', [$this, 'restrictUploadMimeTypes'] );

        if(constant('WP_DEBUG') && !Config::get('app.errors.deprecated_file')) {
            add_action('deprecated_file_included', function() {
                add_filter('deprecated_file_trigger_error', '__return_false');
            });
        }
    }

    /**
     * User Profile Hook
     *
     * @param $user
     */
    public function userProfiles($user) {
        echo BaseForm::nonceInput('hook');
        echo '<div class="typerocket-wp-style-table">';

        /**
         * @depreciated action tr_user_profile
         */
        if( has_action('typerocket_user_profile') ) {
            do_action('typerocket_user_profile', $user );
        }

        if( has_action('typerocket_user_fields') ) {
            $form = Helper::form();
            do_action('typerocket_user_fields', $form, $user );
        }
        echo '</div>';
    }

    /**
     * Menu Fields
     *
     * @param $item_id
     * @param $item
     * @param $depth
     * @param $args
     * @param $id
     */
    public function menuFields($item_id, $item, $depth, $args, $id) {
        if(has_action('typerocket_menu_fields')) {
            echo BaseForm::nonceInput('hook');
            $id = 'tr-fields-' . wp_generate_uuid4();
            echo '<div class="tr-menu-container typerocket-wp-style-subtle" id="'.$id.'">';
            $form = Helper::form($item)->useMenu($item_id);
            do_action('typerocket_menu_fields', $form, $item_id, $item, $depth, $args, $id);
            echo '<script>if(window.tr_apply_repeater_callbacks !== undefined) { window.tr_apply_repeater_callbacks(jQuery("#'.$id.'")) }</script>';
            echo '</div>';
        }
    }

    /**
     * Is Front-end enabled
     *
     * @return bool
     */
    public function frontendIsEnabled()
    {
        return $this->frontend_mode;
    }

    /**
     * Enable Front-end
     *
     * @return static
     */
    public function frontendEnable()
    {
        if($this->frontend_mode) {
            return $this;
        }

        $this->frontend_mode = true;

        add_action( 'wp_enqueue_scripts', function() {wp_enqueue_style( 'dashicons' );} );
        add_action( 'wp_enqueue_scripts', [ $this, 'addCss' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'addJs' ] );
        add_action( 'wp_head', [ $this, 'addTopJs' ] );
        add_action( 'wp_footer', [ $this, 'addBottomJs' ] );

        return $this;
    }

    /**
     * Maybe Init Front-end
     *
     * @param bool $force for typerocket on the front-end
     *
     * @return bool
     */
    public function maybeFrontend($force = false)
    {
        $this->frontend_mode = $force || Config::get('app.frontend');

        if ( is_admin() || !$this->frontend_mode ) {
            $this->frontend_mode = false;
            return $this->frontend_mode;
        }

        $this->frontendEnable();

        return $this->frontend_mode;
    }

    /**
     * Load plugins
     */
    public function loadExtensions()
    {
        $conf = Config::get('app');
        $ext = apply_filters('typerocket_extensions', $conf['extensions'] );

        foreach ($ext as $extClass) {
            if(class_exists($extClass)) {
                (new Resolver())->resolve($extClass);
                $this->loadedExtensions[] = $extClass;
            }
        }
    }

    /**
     * Load Services
     */
    public function loadServices()
    {
        // Application Services
        $conf = Config::get('app');
        $services = apply_filters('typerocket_services', $conf['services'] );

        /**
         * @var string[] $services
         */
        foreach ($services as $service) {
            $instance = (new Resolver)->resolve($service);
            if($instance instanceof Service) {
                Container::register($service, [$instance, 'register'], $instance->isSingleton(), $instance::ALIAS);
                $this->loadedServices[] = get_class($instance);
            }
        }
    }

    /**
     * Load Responders
     */
    public function loadResponders() {
        if( defined('WP_INSTALLING') && WP_INSTALLING) {
            return;
        }

        add_action( 'save_post', 'TypeRocket\Http\Responders\Hook::posts' );
        add_action( 'edit_attachment', 'TypeRocket\Http\Responders\Hook::attachments' );
        add_action( 'wp_insert_comment', 'TypeRocket\Http\Responders\Hook::comments' );
        add_action( 'edit_comment', 'TypeRocket\Http\Responders\Hook::comments' );
        add_action( 'edit_term', 'TypeRocket\Http\Responders\Hook::taxonomies', 10, 4 );
        add_action( 'create_term', 'TypeRocket\Http\Responders\Hook::taxonomies', 10, 4 );
        add_action( 'profile_update', 'TypeRocket\Http\Responders\Hook::users' );
        add_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );
    }

    /**
     *  Set flashing for admin notices
     */
    public function setFlash() {
        if( !empty($_COOKIE[Redirect::KEY_ADMIN]) ) {
            $data = (new Cookie)->getTransient(Redirect::KEY_ADMIN);
            Notice::dismissible($data);
        }
    }

    /**
     * Add CSS
     */
    public function addCss()
    {
        $url = $this->stash['url.typerocket'];
        $manifest = $this->stash['manifest.typerocket'];

        wp_enqueue_style( 'typerocket-styles', $url . $manifest['/css/core.css']);

        if (is_admin()) {
            wp_enqueue_style( 'wp-color-picker' );
        }
    }

    /**
     * Add JavaScript
     */
    public function addJs()
    {
        $url = $this->stash['url.typerocket'];
        $manifest = $this->stash['manifest.typerocket'];

        wp_enqueue_script( 'typerocket-scripts-global', $url . $manifest['/js/global.js'] );
    }

    /**
     * Restrict Upload Mime Types
     *
     * https://wordpress.stackexchange.com/a/97025
     * https://wordpress.stackexchange.com/a/174805
     *
     * @param $file
     * @return mixed
     */
    public function restrictUploadMimeTypes($file) {
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
        $file['error'] = __('Sorry, you cannot upload this file type for this field.', 'typerocket-domain');
        return $file;
    }

    /**
     * Add JavaScript to very bottom
     *
     * This is in place so that all other scripts from fields come
     * before the main typerocket script.
     */
    public function addBottomJs()
    {
        $url = $this->stash['url.typerocket'];
        $manifest = $this->stash['manifest.typerocket'];

        wp_enqueue_script( 'typerocket-scripts', $url . $manifest['/js/core.js'], [ 'jquery', 'wp-i18n' ], false, true );
        wp_set_script_translations( 'typerocket-scripts', 'typerocket-domain' );
        do_action('typerocket_bottom_assets', $url, $manifest);
    }

    /**
     * Top JavaScript
     */
    public function addTopJs()
    {
        $url = $this->stash['url.typerocket'];
        $manifest = $this->stash['manifest.typerocket'];
        $scheme = '';
        if ( is_ssl() || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
            $scheme =  'https';
        }
        ?><script>window.trHelpers = {admin_uri: "<?php echo rtrim(admin_url('', $scheme ), '/');?>",site_uri: "<?php echo rtrim(esc_url(get_site_url( null, '', $scheme )), '/');?>", nonce: "<?php echo Response::new()->createNonce(); ?>"}</script><?php
        do_action('typerocket_top_assets', $url, $manifest);
    }

    /**
     * Add Rewrite rules
     */
    public static function addRewrites()
    {
        $path = Request::new()->getPath();

        if(Str::contains('tr-api', $path) || Config::get('app.debug') )
        {
            Route::new()->any()
                ->match('tr-api/rest/([^/]+)/?([^/]+)?', ['resource', 'id'])
                ->do([RestController::class, 'rest']);

            Route::new()->post()
                ->match('tr-api/(builder|matrix)/([^/]+)/([^/]+)', ['caller', 'group', 'type'])
                ->do([FieldsController::class, 'component']);

            Route::new()->get()->post()
                ->match('tr-api/search')->middleware('search')
                ->do([FieldsController::class, 'search']);
        }
    }

    /**
     * Check site state
     */
    public function checkSiteStateChanged() {
        if ( $site_state = get_option(static::STATE) ) {

            if( is_array( $site_state ) ) {
                $site_state = array_unique( $site_state );
                foreach ( $site_state as $site_state_func ) {
                    if( function_exists( $site_state_func ) ) {
                        call_user_func( $site_state_func );
                    }
                }
            }

            update_option(static::STATE, '0', 'yes');
        }
    }

    /**
     * Updates _typerocket_site_state_changed option in database
     *
     * Should be called when a theme or plugin has been activated or deactivated.
     * Used to facilitate tasks like flushing rewrite rules for the registration
     * and de-registration of post types and taxonomies.
     *
     * @link https://core.trac.wordpress.org/ticket/47526
     *
     * @param string|array $arg single function name or list of function names
     */
    public static function updateSiteState($arg)
    {
        $value = [];

        if ($state = get_option(static::STATE)) {
            $value = maybe_unserialize($state);

            if (!is_array($value)) {
                $value = [];
            }
        }

        if (is_array($arg)) {
            $value = array_merge($value, $arg);
        } else {
            $value[] = $arg;
        }

        update_option(static::STATE, array_unique($value), 'yes');
    }

    /**
     * Add TypeRocket Information to WordPress Site Health
     *
     * @param $info
     * @return array
     */
    public function health($info) : array
    {
        $paths = Config::getFromContainer()->locate('paths');
        $urls = Config::getFromContainer()->locate('urls');
        $plugin_version = defined('TYPEROCKET_PLUGIN_VERSION') ? TYPEROCKET_PLUGIN_VERSION : null;

        try {
            $core_version = InstalledVersions::getVersion('typerocket/core');
        } catch (\Throwable $e) {
            $core_version = __('Another plugin is blocking this information.', 'typerocket-core');
        }

        try {
            $pro_version = InstalledVersions::getVersion('typerocket/professional');
        } catch (\Throwable $e) {
            $pro_version = __('NA', 'typerocket-core');
        }

        return array_merge([
            'typerocket' => [
                'label'  => __( 'TypeRocket', 'typerocket-core' ),
                'fields' => [
                    'path-app'                => [
                        'label' => __( 'App Path', 'typerocket-core' ),
                        'value' => $paths['app'],
                    ],
                    'path-config'                => [
                        'label' => __( 'Config Path', 'typerocket-core' ),
                        'value' => Config::getFromContainer()->getRoot(),
                    ],
                    'path-assets'                => [
                        'label' => __( 'Assets Path', 'typerocket-core' ),
                        'value' => $paths['assets'],
                    ],
                    'url-assets'                => [
                        'label' => __( 'Assets URL', 'typerocket-core' ),
                        'value' => $urls['assets'],
                    ],
                    'extensions-active' => [
                        'label' => __( 'Loaded Extensions', 'typerocket-core' ),
                        'value' => implode(", ", $this->loadedExtensions),
                    ],
                    'services-active' => [
                        'label' => __( 'Loaded Services', 'typerocket-core' ),
                        'value' => implode(", ", $this->loadedServices),
                    ],
                    'version-core'                => [
                        'label' => __( 'Core Version', 'typerocket-core' ),
                        'value' => $core_version,
                    ],
                    'version-pro'                => [
                        'label' => __( 'Pro Version', 'typerocket-core' ),
                        'value' => $pro_version,
                    ],
                    'app-type' => [
                        'label' => __( 'App Type', 'typerocket-core' ),
                        'value' => $plugin_version ? "Plugin Version {$plugin_version}": 'Custom/Composer',
                    ],
                ]
            ]
        ], $info);
    }

    /**
     * @return static
     */
    public static function getFromContainer()
    {
        return Container::resolve(static::ALIAS);
    }
}
