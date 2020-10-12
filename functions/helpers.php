<?php
if( ! function_exists('dd') ) {
    /**
     * Die and Dump Vars
     *
     * @param mixed $param
     */
    function dd($param) {
        call_user_func_array('var_dump', func_get_args());
        exit();
    }
}

if( ! function_exists('mb_ucwords') ) {
    /**
     * String ends with
     *
     * @param string $str
     * @param string $delimiters not used but is added for future compatibility
     *
     * @return bool
     */
    function mb_ucwords( $str, $delimiters = " \t\r\n\f\v" ) {
        return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
    }
}

if( ! function_exists('dots_walk') ) {
    /**
     * Dots Walk
     *
     * Traverse array with dot notation.
     *
     * @param string $dots dot notation key.next.final
     * @param array|object $array an array to traverse
     * @param null|mixed $default
     *
     * @return array|mixed|null
     */
    function dots_walk($dots, $array, $default = null)
    {
        $traverse = explode('.', $dots);
        foreach ($traverse as $step) {
            $v = is_object($array) ? ($array->$step ?? null) : ($array[$step] ?? null);

            if ( !isset($v) && ! is_string($array) ) {
                return $default;
            }
            $array = $v ?? $default;
        }

        return $array;
    }
}

if( ! function_exists('dots_set') ) {
    /**
     * Dots Set
     *
     * Set an array value using dot notation.
     *
     * @param string $dots dot notation path to set
     * @param array $array the original array
     * @param mixed $value the value to set
     *
     * @return array
     */
    function dots_set($dots, array $array, $value)
    {
        $set      = &$array;
        $traverse = explode('.', $dots);
        foreach ($traverse as $step) {
            $set = &$set[$step];
        }
        $set = $value;

        return $array;
    }
}

if ( ! function_exists('immutable')) {
    /**
     * Get Constant Variable
     *
     * @param string $name the constant variable name
     * @param null|mixed $default The default value
     *
     * @return mixed
     */
    function immutable($name, $default = null) {
        return defined($name) ? constant($name) : $default;
    }
}

if ( ! function_exists('array_reduce_allowed_str')) {

    /**
     * HTML class names helper
     *
     * @param array $array
     *
     * @return string
     */
    function array_reduce_allowed_str($array) {
        $reduced = '';
        array_walk($array, function($val, $key) use(&$reduced) {
            $reduced .= $val ? " $key" : '';
        });
        $cleaned = implode(' ', array_unique(array_map('trim', explode(' ', trim($reduced)))));
        return $cleaned;
    }
}

if ( ! function_exists('class_names')) {

    /**
     * HTML class names helper
     *
     * @param string $defaults
     * @param null|array $classes
     * @param string $failed
     * @return string
     */
    function class_names($defaults, $classes = null, $failed = '') {
        if(!$result = array_reduce_allowed_str(is_array($defaults) ? $defaults : $classes)) {
            $result = !is_array($classes) ? $classes : $failed;
        }

        $defaults = !is_array($defaults) ? $defaults : '';

        return $defaults . ' ' . $result;
    }
}

if(! function_exists('not_blank_string')) {
    /**
     * Not blank string
     *
     * @param string|null $value
     *
     * @return bool
     */
    function not_blank_string($value) {
        return !(!isset($value) || $value === '');
    }
}

if ( ! function_exists('database')) {
    /**
     * Get WPDB
     *
     * @return \wpdb
     */
    function database() {
        global $wpdb;
        return $wpdb;
    }
}

if( ! function_exists('tr_app_class') ) {
    /**
     * Get Namespaced Class
     *
     * @param string $append
     * @return string
     */
    function tr_app_class($append) {
        $space = "\\" . TR_APP_NAMESPACE . "\\";
        return $space . ltrim($append, '\\');
    }
}

if( ! function_exists('tr_wp_root') ) {
    /**
     * Get WordPress Root
     *
     * @return string
     */
    function tr_wp_root() {

        if( defined('TR_ROOT_WP') ) {
            return TR_ROOT_WP;
        }

        if( defined('TR_ROOT_INSTALL') ) {
            return TR_PATH . '/' . trim(tr_config('app.root.wordpress', 'wordpress'), '/');
        }

        if( defined('ABSPATH') ) {
            return ABSPATH;
        }

        $depth = TR_PATH;
        $looking = 5;
        while ($looking--) {
            if(is_file($depth . '/wp-load.php')) {
                if(is_file($depth . '/wp-includes/wp-db.php')) {
                    return $depth;
                }
            }
            $depth .= '/..';
        }

        return false;
    }
}

if ( ! function_exists('tr_container')) {
    /**
     * Resolve Class From DI Container
     *
     * Get from DI container
     *
     * @param string $class_name class or alias
     * @return mixed|null
     */
    function tr_container($class_name) {
        return \TypeRocket\Core\Injector::resolve($class_name);
    }
}

if ( ! function_exists('tr_resolve')) {
    /**
     * Resolve Class
     *
     * Inject all class dependencies
     *
     * @param string $class_name class or alias
     *
     * @return mixed|null
     * @throws Exception
     */
    function tr_resolve($class_name) {
        return (new \TypeRocket\Core\Resolver)->resolve($class_name);
    }
}

if( ! function_exists('tr_update_site_state') ) {
    /**
     * Updates _site_state_changed option in database
     *
     * Should be called when a theme or plugin has been activated or deactivated.
     * Used to facilitate tasks like flushing rewrite rules for the registration
     * and de-registration of post types and taxonomies.
     *
     * @link https://core.trac.wordpress.org/ticket/47526
     *
     * @param string|array $arg single function name or list of function names
     */
    function tr_update_site_state($arg)
    {
        $value = [];

        if ($state = get_option('_tr_site_state_changed')) {
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

        update_option('_tr_site_state_changed', array_unique($value), 'yes');
    }
}

if ( ! function_exists('tr_config')) {
    /**
     * Locate Config Setting
     *
     * Traverse array with dot notation.
     *
     * @param string $dots dot notation key.next.final
     * @param null|mixed $default default value to return if null
     *
     * @return array|mixed|null
     */
    function tr_config($dots, $default = null) {
        $config = \TypeRocket\Core\Config::getFromContainer();
        return $config->locate($dots, $default);
    }
}

if ( ! function_exists('tr_storage_path')) {
    /**
     * Storage File Path
     *
     * @param string|null $path storage file path
     *
     * @return array|mixed|null
     */
    function tr_storage_path($path = null) {
        return tr_config('paths.storage') . ( $path ? '/' . ltrim($path, '/') : '' );
    }
}

if ( ! function_exists('tr_wp_uploads_path')) {
    /**
     * Storage File Path
     *
     * @param string|null $path storage file path
     *
     * @return array|mixed|null
     */
    function tr_wp_uploads_path($path = null) {
        return WP_CONTENT_DIR. '/uploads' . ( $path ? '/' . ltrim($path, '/') : '' );
    }
}

if( ! function_exists('tr_response') ) {
    /**
     * Get Main Response
     *
     * @return \TypeRocket\Http\Response
     */
    function tr_response() {
        return \TypeRocket\Http\Response::getFromContainer();
    }
}

if( ! function_exists('tr_request') ) {
    /**
     * Get Request
     *
     * @param null $method
     * @return \TypeRocket\Http\Request
     */
    function tr_request() {
        return new \TypeRocket\Http\Request;
    }
}

if( ! function_exists('tr_debug') ) {
    /**
     * Get Debug
     *
     * @return bool
     */
    function tr_debug() {

        return tr_config('app.debug');
    }
}

if( ! function_exists('tr_assets_url') ) {
    /**
     * Get Assets URL
     *
     * @param string $append
     * @return string
     */
    function tr_assets_url( $append ) {
        $root = tr_config('urls.assets');
        return $root . '/' . ltrim($append, '/');
    }
}


if ( ! function_exists('tr_views_path') ) {
    /**
     * Get Views Directory
     *
     * @param string $append
     * @return string
     */
    function tr_views_path( $append ) {
        $root = tr_config('paths.views');
        return $root . '/' . ltrim($append, '/');
    }
}

if ( ! function_exists('tr_controller') ) {
    /**
     * Get controller by recourse
     *
     * @param string $resource use the resource name to get controller
     *
     * @return null|string $controller
     */
    function tr_controller($resource)
    {
        if(is_string($resource) && $resource[0] == '@') {
            $resource = substr($resource, 1);
        }

        if(\TypeRocket\Utility\Str::ends('Controller', $resource)) {
            $resource = substr($resource, 0, -10);
        }

        $Resource = TypeRocket\Utility\Str::camelize($resource);
        $controller    = tr_app_class("Controllers\\{$Resource}Controller");
        return $controller;
    }
}

if ( ! function_exists('tr_model') ) {
    /**
     * Get model by recourse
     *
     * @param string $resource use the resource name to get model
     * @param bool $instance
     *
     * @return null|string|\TypeRocket\Models\Model $object
     */
    function tr_model($resource, $instance = true)
    {
        if(is_string($resource) && $resource[0] == '@') {
            $resource = substr($resource, 1);
        }

        $Resource = TypeRocket\Utility\Str::camelize($resource);
        $model    = tr_app_class("Models\\{$Resource}");
        return $instance ? new $model : $model;
    }
}

if ( ! function_exists('tr_taxonomy')) {
    /**
     * Register taxonomy
     *
     * @param string $singular
     * @param null $plural
     * @param array $settings
     *
     * @return \TypeRocket\Register\Taxonomy
     */
    function tr_taxonomy($singular, $plural = null, $settings = [])
    {
        $obj = new \TypeRocket\Register\Taxonomy($singular, $plural, $settings);
        $obj->addToRegistry();

        return $obj;
    }
}

if ( ! function_exists('tr_post_type')) {
    /**
     * Register post type
     *
     * @param string $singular Singular name for post type
     * @param string|null $plural Plural name for post type
     * @param array $settings The settings for the post type
     *
     * @return \TypeRocket\Register\PostType
     */
    function tr_post_type($singular, $plural = null, $settings = [])
    {
        $obj = new \TypeRocket\Register\PostType($singular, $plural, $settings);
        $obj->addToRegistry();

        return $obj;
    }
}

if ( ! function_exists('tr_meta_box')) {
    /**
     * Register meta box
     *
     * @param string $name
     * @param null|string|array $screen
     * @param array $settings
     *
     * @return \TypeRocket\Register\MetaBox
     */
    function tr_meta_box($name = null, $screen = null, $settings = [])
    {
        $obj = new \TypeRocket\Register\MetaBox($name, $screen, $settings);
        $obj->addToRegistry();

        return $obj;
    }
}

if ( ! function_exists('tr_page')) {
    /**
     * @param string $resource
     * @param string $action
     * @param string $title
     * @param array $settings
     * @param null|array|string|callable $handler
     *
     * @return \TypeRocket\Register\Page
     */
    function tr_page($resource, $action, $title, array $settings = [], $handler = null)
    {
        $obj = new \TypeRocket\Register\Page($resource, $action, $title, $settings, $handler);
        $obj->addToRegistry();

        return $obj;
    }
}

if ( ! function_exists('tr_resource_pages')) {
    /**
     * @param string $singular
     * @param string|array $plural
     * @param array $settings
     * @param null $resource
     * @param null $handler
     * @return \TypeRocket\Register\Page
     */
    function tr_resource_pages($singular, $plural = null, array $settings = [], $resource = null, $handler = null)
    {
        [$singular, $handle] = array_pad(explode('@', $singular), 2, null);
        $handler = $handler ?? $handle;

        if(is_array($plural)) {
            $settings = $plural;

            if(isset($settings['plural'])) {
                $plural = $settings['plural'];
                unset($settings['plural']);
            } else {
                $plural = null;
            }
        }

        if ( ! $plural) {
            $plural = \TypeRocket\Utility\Inflect::pluralize($singular);
        }

        if(!$handler) {
            $handler = tr_controller($singular);
        }

        if( ! $resource) {
            $resource = $singular;
        }

        $menu_id = 'add_resource_' . \TypeRocket\Utility\Sanitize::underscore($singular);

        $add = tr_page($resource, 'add', __('Add ' . $singular))
            ->setMenuTitle(__('Add New'))
            ->adminBar($menu_id, $singular, 'new-content')
            ->mapActions([
                'GET' => 'add',
                'POST' => 'create',
            ]);

        $delete = tr_page($resource, 'delete', 'Delete ' . $singular)
            ->removeMenu()
            ->mapActions([
                'GET' => 'delete',
                'DELETE' => 'destroy',
            ]);

        $show = tr_page($resource, 'show', $singular)
            ->addNewButton()
            ->removeMenu()
            ->mapActions([
                'GET' => 'show'
            ]);

        $edit = tr_page($resource, 'edit', __('Edit ' . $singular))
            ->addNewButton()
            ->removeMenu()
            ->mapActions([
                'GET' => 'edit',
                'PUT' => 'update',
            ]);

        $index = tr_page($resource, 'index', $plural, $settings)
            ->apply($edit, $show, $delete, $add)
            ->setSubMenuTitle(__('All ' . $plural))
            ->addNewButton();

        foreach ([$add, $edit, $delete, $show, $index] as $page) {
            /** @var \TypeRocket\Register\Page $page */
            $page->setHandler($handler);
        }

        return $index;
    }
}

if ( ! function_exists('tr_tabs')) {
    /**
     * Create tabs
     *
     * @return \TypeRocket\Elements\Tabs
     */
    function tr_tabs()
    {
        return new \TypeRocket\Elements\Tabs();
    }
}

if ( ! function_exists('tr_form')) {
    /**
     * Instance the From
     *
     * @param string|\TypeRocket\Interfaces\Formable|array $resource posts, users, comments, options your own
     * @param string|null $action update, delete, or create
     * @param null|int $item_id you can set this to null or an integer
     * @param mixed|null|string $model
     *
     * @return \TypeRocket\Elements\BaseForm|\App\Elements\Form
     */
    function tr_form($resource = null, $action = null, $item_id = null, $model = null)
    {
        $form = tr_config('app.class.form');

        return new $form($resource, $action, $item_id, $model);
    }
}

if ( ! function_exists('tr_model_field')) {
    /**
     * Modify Model Value
     *
     * @param \TypeRocket\Models\Model $model use dot notation
     * @param mixed $args
     *
     * @return array|mixed|null|string
     */
    function tr_model_field($model, $args)
    {
        if(!empty($args[0]) && $args[0] != ':' && !is_array($args)) {
            return $model->getFieldValue($args);
        }

        if(is_array($args)) {
            $value = $model->getFieldValue($args['name']);
            $callback_args = array_merge([$value], $args['args']);
            return call_user_func_array($args['callback'], $callback_args);
        }

        [$modifier, $arg1, $arg2] = array_pad(explode(':', ltrim($args, ':'), 3), 3, null);
        $name = $arg2 ? $arg2 : $arg1 ;
        $value = $model->getFieldValue($name);

        switch($modifier) {
            case 'img';
                $size = $arg2 ? $arg1 : 'thumbnail' ;
                $modified = wp_get_attachment_image( (int) $value, $size);
                break;
            case 'plaintext';
                $modified = wpautop(esc_html($value));
                break;
            case 'html';
                $modified = \TypeRocket\Utility\Sanitize::editor($value, true);
                break;
            case 'img_src';
                $size = $arg2 ? $arg1 : 'thumbnail';
                $modified = wp_get_attachment_image_src( (int) $value, $size);
                break;
            case 'background';
                $size = $arg2 ? $arg1 : 'full';
                $img_src = wp_get_attachment_image_src( (int) $value['id'] ?? 0, $size)[0];
                $img_px = $value['x'] ?? 50;
                $img_py = $value['y'] ?? 50;
                $img_p = "{$img_px}% {$img_py}%";
                $modified = "background-image: url({$img_src}); background-position: {$img_p};";
                break;
            case 'post';
                $modified = get_post( (int) $value);
                break;
            case 'term';
                $taxonomy = $arg2 ? $arg1 : 'category' ;
                $modified = get_term($value, $taxonomy);
                break;
            default:
                $callback_args = array_merge([$value], $arg2 ? [$arg1] : []);
                $modified = call_user_func_array($modifier, $callback_args);
                break;
        }

        return $modified;
    }
}

if ( ! function_exists('tr_post_field')) {
    /**
     * Get the post's field
     *
     * @param string $name use dot notation
     * @param null|int|WP_Post $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_post_field($name, $item_id = null)
    {
        global $post;

        if (is_null($item_id) && isset($post->ID)) {
            $item_id = $post->ID;
        }

        try {
            $model = new \TypeRocket\Models\WPPost();
            $model->wpPost($item_id);
        } catch (\Exception $e) {
            return null;
        }

        return tr_model_field($model, $name);
    }
}

if ( ! function_exists('tr_field')) {
    /**
     * Get the post field
     *
     * @param string $name use dot notation
     * @param null|int|WP_Post $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_field($name, $item_id = null)
    {
        return tr_post_field($name, $item_id);
    }
}

if ( ! function_exists('tr_components_field')) {
    /**
     * Get components
     *
     * Auto binding only for post types
     *
     * @param string $name use dot notation
     * @param null $item_id
     *
     * @param null|string $modelClass
     *
     * @return array|mixed|null|string
     * @throws Exception
     */
    function tr_components_field($name, $item_id = null, $modelClass = null)
    {
        global $post;

        if (isset($post->ID) && is_null($item_id)) {
            $item_id = $post->ID;
        }

        /** @var \TypeRocket\Models\Model $model */
        $modelClass = $modelClass ?? \TypeRocket\Models\WPPost::class;
        $model = new $modelClass;
        $model->findById($item_id);

        $builder_data = $model->getFieldValue($name);
        if(is_array($builder_data)) {
            tr_components_loop($builder_data, compact('name', 'item_id', 'model'));
        }

        return $builder_data;
    }
}

if( ! function_exists('tr_components_loop')) {
    /**
     * Loop Components
     *
     * @param array $builder_data
     * @param array $other be sure to pass $name, $item_id, $model
     */
    function tr_components_loop($builder_data, $other = []) {
        /**
         * @var $name
         * @var $item_id
         * @var $model
         * @var $nested
         */
        extract($other);
        $model = $model ?? null;
        $item_id = $item_id ?? null;
        $nested = $nested ?? false;
        $i = $nested ? 1 : 0;
        $len = count($builder_data);
        $components_list = tr_config('components.registry');
        do_action('tr_components_loop', $builder_data, $other, $len);
        foreach ($builder_data as $hash => $data) {
            $first_item = $last_item = false;

            if ($i == 0) {
                $first_item = true;
            } else if ($i == $len - 1) {
                $last_item = true;
            }

            $component_id = key($data);
            $component = strtolower(key($data));
            $info = compact('name', 'item_id', 'model', 'first_item', 'last_item', 'component_id', 'hash');
            $component_class = $components_list[$component];

            if(!$component_class) {
                $class = (new \TypeRocket\Template\ErrorComponent)->title($component);
            } else {
                /** @var $class \TypeRocket\Template\Component */
                $class = new $component_class;
            }

            $class->render($data[$component_id], $info);
            $i++;
        }
    }
}

if ( ! function_exists('tr_user_field')) {
    /**
     * Get users field
     *
     * @param string $name use dot notation
     * @param null $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_user_field($name, $item_id = null)
    {
        global $user_id, $post;

        if (isset($user_id) && is_null($item_id)) {
            $item_id = $user_id;
        } elseif (is_null($item_id) && isset($post->ID)) {
            $item_id = $post->post_author;
        } elseif (is_null($item_id)) {
            $item_id = get_current_user_id();
        }

        /** @var \TypeRocket\Models\WPUser $model */
        $model = tr_model('User');
        $model->wpUser($item_id);

        return tr_model_field($model, $name);
    }
}

if ( ! function_exists('tr_option_field')) {
    /**
     * Get options
     *
     * @param string $name use dot notation
     *
     * @return array|mixed|null|string
     */
    function tr_option_field($name)
    {
        $model = tr_model('Option');

        return tr_model_field($model, $name);
    }
}

if ( ! function_exists('tr_comment_field')) {
    /**
     * Get comments field
     *
     * @param string $name use dot notation
     * @param null $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_comment_field($name, $item_id = null)
    {
        global $comment;

        if (isset($comment->comment_ID) && is_null($item_id)) {
            $item_id = $comment->comment_ID;
        }

        /** @var \TypeRocket\Models\WPComment $model */
        $model = tr_model('Comment');
        $model->wpComment($item_id);

        return tr_model_field($model, $name);
    }
}

if ( ! function_exists('tr_term_field')) {
    /**
     *  Get taxonomy field
     *
     * @param string $name use dot notation
     * @param string $taxonomy taxonomy model class
     * @param int $item_id taxonomy id
     *
     * @return array|mixed|null|string
     */
    function tr_term_field($name, $taxonomy = null, $item_id = null)
    {
        try {
            /** @var \TypeRocket\Models\WPTerm $model */
            $model = $taxonomy ? tr_model($taxonomy) : new \TypeRocket\Models\WPTerm;
            $model->wpTerm($item_id);
        } catch (\Exception $e) {
            return null;
        }

        return tr_model_field($model, $name);
    }
}

if ( ! function_exists('tr_resource_field')) {
    /**
     * Get resource
     *
     * @param string $name use dot notation
     * @param string $resource
     * @param null|int $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_resource_field($name, $resource, $item_id = null)
    {
        /** @var \TypeRocket\Models\Model $model */
        $model = tr_model($resource);
        $model->findById($item_id);

        return tr_model_field($model, $name);
    }
}

if ( ! function_exists('tr_is_json')) {
    /**
     * Detect is JSON
     *
     * @param $args
     *
     * @return bool
     */
    function tr_is_json(...$args)
    {
        if(is_array($args[0]) || is_object($args[0])) {
            return false;
        }

        if (trim($args[0]) === '') {
            return false;
        }

        json_decode(...$args);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

if ( ! function_exists('tr_redirect')) {
    /**
     * @return \TypeRocket\Http\Redirect
     */
    function tr_redirect()
    {
        return new \TypeRocket\Http\Redirect();
    }
}

if ( ! function_exists('tr_redirect_message')) {
    /**
     * @param null|array $default
     * @param bool $delete
     *
     * @return array
     */
    function tr_redirect_message($default = null, $delete = true)
    {
        $data = (new \TypeRocket\Http\Cookie)->getTransient('tr_redirect_message', $delete);

        return ! is_null($data) ? $data : $default;
    }
}

if ( ! function_exists('tr_redirect_errors')) {
    /**
     * @param null $default
     *
     * @return array
     */
    function tr_redirect_errors($default = null)
    {
        /** @var \TypeRocket\Http\ErrorCollection $errors */
        $errors = \TypeRocket\Utility\RuntimeCache::getFromContainer()->get(\TypeRocket\Http\ErrorCollection::KEY);
        $errors = $errors->errors();

        return ! is_null($errors) ? $errors : $default;
    }
}

if ( ! function_exists('tr_redirect_data')) {
    /**
     * @param null $default
     * @param bool $delete
     *
     * @return array
     */
    function tr_redirect_data($default = null, $delete = true)
    {
        $data = (new \TypeRocket\Http\Cookie)->getTransient('tr_redirect_data', $delete);

        return ! is_null($data) ? $data : $default;
    }
}

if ( ! function_exists('tr_field_nonce')) {
    /**
     * TypeRocket Nonce Field
     *
     * @param string $action
     *
     * @return string
     */
    function tr_field_nonce($action = '')
    {
        return wp_nonce_field('form_' . $action . tr_config('app.seed'), '_tr_nonce_form' . $action, false, false);
    }
}

if( ! function_exists('tr_nonce')) {
    /**
     * TypeRocket Nonce
     *
     * @param string $action
     *
     * @return false|string
     */
    function tr_nonce($action = '') {
        return wp_create_nonce( 'form_' . $action . tr_config('app.seed' ) );
    }
}

if( ! function_exists('tr_field_nonce_check')) {
    /**
     * TypeRocket Check Field Nonce
     *
     * Works the same as check_ajax_referer but also include
     * request header checks for: X-CSRF-TOKEN and X-WP-NONCE
     *
     * @param string $action
     * @param bool $die
     *
     * @return bool|int
     */
    function tr_field_nonce_check($action = '', $die = false) {

        $query_arg = '_tr_nonce_form'.$action;
        $action = 'form_' . $action . tr_config('app.seed');
        $nonce = '';

        if ( isset( $_REQUEST[$query_arg] ) ) {
            $nonce = $_REQUEST[$query_arg];
        } elseif ( isset( $_REQUEST['_ajax_nonce'] ) ) {
            $nonce = $_REQUEST['_ajax_nonce'];
        } elseif ( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = $_REQUEST['_wpnonce'];
        } elseif ( isset( $_SERVER['HTTP_X_CSRF_TOKEN'] ) ) {
            $nonce = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
            $nonce = $_SERVER['HTTP_X_WP_NONCE'];
        }

        $result = wp_verify_nonce( $nonce, $action );
        do_action( 'check_ajax_referer', $action, $result );

        if ( $die && false === $result ) {
            if ( wp_doing_ajax() ) {
                wp_die( -1, 403 );
            } else {
                die( '-1' );
            }
        }

        return $result;
    }
}

if ( ! function_exists('tr_field_method')) {
    /**
     * TypeRocket Nonce Field
     *
     * @param string $method GET, POST, PUT, PATCH, DELETE
     *
     * @return string
     */
    function tr_field_method($method = 'POST')
    {
        return "<input type=\"hidden\" name=\"_method\" value=\"{$method}\"  />";
    }
}

if ( ! function_exists('tr_form_hidden_fields')) {
    /**
     * TypeRocket Nonce Field
     *
     * @param string $method GET, POST, PUT, PATCH, DELETE
     *
     * @return string
     */
    function tr_form_hidden_fields($method = 'POST')
    {
        return tr_field_method($method) . tr_field_nonce();
    }
}

if ( ! function_exists('tr_cookie')) {
    /**
     * @return \TypeRocket\Http\Cookie
     */
    function tr_cookie()
    {
        return new \TypeRocket\Http\Cookie();
    }
}

if ( ! function_exists('tr_old_field')) {
    /**
     * @param string $name the name of the field
     * @param string $default a default value
     * @param bool $delete should delete old data when getting the last field
     *
     * @return string
     */
    function tr_old_field($name, $default = '', $delete = false)
    {
        $data = tr_cookie()->getTransient('tr_old_fields', $delete);

        return isset($data[$name]) ? $data[$name] : $default;
    }
}

if ( ! function_exists('tr_old_fields')) {
    /**
     * @param null $default
     * @param bool $delete
     *
     * @return string
     */
    function tr_old_fields($default = null, $delete = true)
    {
        $data = tr_cookie()->getTransient('tr_old_fields', $delete);

        return ! is_null($data) ? $data : $default;
    }
}

if ( ! function_exists('tr_old_fields_remove')) {
    /**
     * @return bool
     */
    function tr_old_fields_remove()
    {
        tr_cookie()->getTransient('tr_old_fields', true);

        return ! (bool) tr_cookie()->getTransient('tr_old_fields');
    }
}

if ( ! function_exists('tr_view')) {
    /**
     * @param string $dots
     * @param array $data
     * @param string $ext
     *
     * @return \TypeRocket\Template\View
     */
    function tr_view($dots, array $data = [], $ext = '.php')
    {
        return new \TypeRocket\Template\View($dots, $data, $ext);
    }
}

if ( ! function_exists('tr_validator')) {
    /**
     * Validate fields
     *
     * @param array $options
     * @param array|null $fields
     * @param null $modelClass
     * @param bool $run
     *
     * @return \TypeRocket\Utility\Validator
     */
    function tr_validator($options, $fields = null, $modelClass = null, $run = false)
    {
        return new \TypeRocket\Utility\Validator($options, $fields, $modelClass, $run);
    }
}

if ( ! function_exists('tr_route')) {
    /**
     * Route
     *
     * @return \TypeRocket\Http\Route
     */
    function tr_route()
    {
        return (new \TypeRocket\Http\Route())->register();
    }
}

if ( ! function_exists('tr_routes_repo')) {
    /**
     * Get Routes Repo
     *
     * @return \TypeRocket\Http\RouteCollection
     */
    function tr_routes_repo()
    {
        return tr_container(\TypeRocket\Http\RouteCollection::ALIAS);
    }
}

if ( ! function_exists('tr_route_find')) {
    /**
     * Get Routes Repo
     * @param string $name
     * @return null|\TypeRocket\Http\Route
     */
    function tr_route_find($name)
    {
        return tr_routes_repo()->getNamedRoute($name);
    }
}

if ( ! function_exists('tr_route_url')) {
    /**
     * Get Routes Repo
     * @param string $name
     * @param array $values
     * @param bool $site
     *
     * @return null|string
     */
    function tr_route_url($name, $values = [], $site = true)
    {
        return tr_route_find($name)->buildUrlFromPattern($values, $site);
    }
}

if ( ! function_exists('tr_query')) {
    /**
     * Database Query
     *
     * @return \TypeRocket\Database\Query
     */
    function tr_query()
    {
        return new \TypeRocket\Database\Query();
    }
}

if ( ! function_exists('tr_file')) {
    /**
     * File Utility
     *
     * @param string $file
     * @return object
     * @throws Exception
     */
    function tr_file($file) {
        return new \TypeRocket\Utility\File($file);
    }
}

if ( ! function_exists('tr_assets_url_build')) {
    /**
     * Config URL
     *
     * @param string $path
     *
     * @return string
     */
    function tr_assets_url_build($path = '') {
        global $wp_actions;

        $path = trim('assets/' . ltrim($path, '/'), '/');
        $plugins_loaded = $url = null;

        if($wp_actions && isset($wp_actions['plugins_loaded'])) {
            $plugins_loaded = true;
        }

        if((defined('TR_THEME_INSTALL') || $plugins_loaded) && function_exists('get_theme_file_uri')) {
            $url = get_theme_file_uri( '/typerocket/wordpress/' . $path );
        }

        if(defined('TR_PLUGIN_INSTALL') && function_exists('plugins_url')) {
            $url = plugins_url( '/typerocket/wordpress/' . $path, TR_PATH );
        }

        if(defined('TR_ROOT_INSTALL')) {
            $url = home_url($path);
        }

        if(!$url || defined('TR_MU_INSTALL')) {
            $mu = immutable('TR_MU_INSTALL', '/typerocket-pro-plugin/typerocket/wordpress/');
            $url = WPMU_PLUGIN_URL . $mu . $path;
        }

        return \TypeRocket\Http\SSL::fixSSLUrl($url);
    }
}

if ( ! function_exists('tr_manifest_cache')) {
    /**
     * Get Asset Version
     *
     * @param string $path
     * @param string $namespace
     * @return mixed
     */
    function tr_manifest_cache($path, $namespace) {
        $manifest = [];

        try {
            $manifest = json_decode(file_get_contents($path), true);
            /** @var \TypeRocket\Utility\RuntimeCache $cache */
            $cache = tr_container('cache');
            $cache->add('manifest', $manifest, $namespace);
        } catch (\Exception $e) {
            tr_report($e, true);
        }

        return $manifest;
    }
}

if ( ! function_exists('tr_manifest')) {
    /**
     * Get Asset Version
     *
     * @param string $namespace
     * @return \TypeRocket\Utility\RuntimeCache
     */
    function tr_manifest($namespace = 'typerocket') {
        return \TypeRocket\Utility\RuntimeCache::getFromContainer()->get('manifest', $namespace);
    }
}

if ( ! function_exists('tr_report')) {
    /**
     * Get Asset Version
     *
     * @param \Throwable $exception
     * @param bool $debug
     * @return void
     */
    function tr_report(\Throwable $exception, $debug = false) {
        $class = tr_config('app.report.error', \TypeRocket\Utility\ExceptionReport::class);
        (new $class($exception, $debug))->report();
    }
}

if ( ! function_exists('tr_auth')) {
    /**
     * @param string $action
     * @param object|string $option
     * @param null|\TypeRocket\Models\AuthUser $user
     * @param \TypeRocket\Auth\Policy|string $policy
     *
     * @return mixed
     * @throws Exception
     */
    function tr_auth($action, $option, $user = null, $policy = null) {
        /** @var \TypeRocket\Services\AuthorizerService  $auth */
        $auth = tr_container('auth');

        if(!$user) {
            $user = tr_container('user');
        }

        return $auth->auth($user, $option, $action, $policy);
    }
}

if ( ! function_exists('tr_abort')) {
    /**
     * Throw HTTP Error
     *
     * @param int $code
     *
     * @return mixed
     */
    function tr_abort(int $code) {
        throw (new \TypeRocket\Exceptions\HttpError)->getRealError($code);
    }
}

if ( ! function_exists('tr_hash')) {
    /**
     * Get Numeric Hash
     *
     * This is not a real uuid. Will only generate a unique id per process.
     *
     * @return integer
     */
    function tr_hash() {
        return wp_unique_id(time());
    }
}

if ( ! function_exists('tr_system')) {
    /**
     * Get Routes Repo
     *
     * @return \TypeRocket\Http\RouteCollection
     */
    function tr_system()
    {
        return tr_container(\TypeRocket\Core\System::ALIAS);
    }
}

if ( ! function_exists('tr_frontend_enable')) {
    /**
     * Enable Front-end
     *
     * @return bool
     */
    function tr_frontend_enable()
    {
        /** @var \TypeRocket\Core\System $system */
        $system = tr_system();

        if(!$system->frontendIsEnabled()) {
            $system->frontendEnable();

            return true;
        }

        return false;
    }
}

if ( ! function_exists('tr_autoload_psr4')) {
    /**
     * Auto loader
     *
     * Array keys include: `init`, `map`, `prefix`, and `folder`
     *
     * @param array $map
     * @param bool $prepend If true, will prepend the autoloader on the autoload stack
     */
    function tr_autoload_psr4(array &$map = [], $prepend = false)
    {
        if (isset($map['init'])) {
            foreach ($map['init'] as $file) {
                require $file;
            }
        }
        spl_autoload_register(function ($class) use (&$map) {
            if (isset($map['map'][$class])) {
                require $map['map'][$class];
                return;
            }
            $prefix = $map['prefix'];
            $folder = $map['folder'];
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $file = $folder . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $len)) . '.php';
            if (is_file($file)) {
                require $file;
                return;
            }
        }, true, $prepend);
    }
}

if ( ! function_exists('tr_sanitize_editor')) {
    /**
     * Sanitize Editor
     *
     * @param $content
     * @param bool $force_filter
     * @param bool $auto_p
     *
     * @return string
     */
    function tr_sanitize_editor($content, $force_filter = true, $auto_p = false)
    {
        return \TypeRocket\Utility\Sanitize::editor($content, $force_filter, $auto_p);
    }
}

if ( ! function_exists('tr_flash_message')) {
    /**
     * @param array|null $data keys include message and type
     * @param bool $dismissible
     *
     * @return false|string|null
     */
    function tr_flash_message($data = null, $dismissible = false)
    {
        if( !empty($_COOKIE['tr_admin_flash']) || !empty($data) ) {
            $flash = (new \TypeRocket\Http\Cookie)->getTransient('tr_admin_flash');

            return \TypeRocket\Elements\Notice::html([
                'message' => $data['message'] ?? $flash['message'] ?? null,
                'type' => $data['type'] ?? $flash['type'] ?? 'info',
            ], $dismissible);
        }

        return null;
    }
}

if ( ! function_exists('tr_nils')) {
    /**
     * @param array|object|\ArrayObject $value
     *
     * @return \TypeRocket\Utility\Nil
     */
    function tr_nils($value)
    {
        return \TypeRocket\Utility\Value::nils($value);
    }
}

if ( ! function_exists('tr_cache')) {
    /**
     * @param string $folder
     *
     * @return \TypeRocket\Utility\PersistentCache
     */
    function tr_cache($folder = 'app')
    {
        return new \TypeRocket\Utility\PersistentCache($folder);
    }
}

if ( ! function_exists('tr_cast')) {
    /**
     * @param mixed $value
     * @param string|callable $type
     *
     * @return bool|float|int|mixed|string
     */
    function tr_cast($value, $type)
    {
        // Integer
        if ($type == 'int' || $type == 'integer') {
            return is_object($value) || is_array($value) ? null : (int) $value;
        }

        // Float
        if ($type == 'float' || $type == 'double' || $type == 'real') {
            return is_object($value) || is_array($value) ? null : (float) $value;
        }

        // JSON
        if ($type == 'json') {

            if(is_serialized($value)) {
                $value = unserialize($value);
            } if(tr_is_json($value)) {
                return $value;
            }

            return json_encode($value);
        }

        // Serialize
        if ($type == 'serialize' || $type == 'serial') {

            if(tr_is_json($value)) {
                $value = json_decode((string) $value, true);
            } if(is_serialized($value)) {
                return $value;
            }

            return serialize($value);
        }

        // String
        if ($type == 'str' || $type == 'string') {
            if(is_object($value) || is_array($value)) {
                $value = json_encode($value);
            } else {
                $value = (string) $value;
            }

            return $value;
        }

        // Bool
        if ($type == 'bool' || $type == 'boolean') {
            return (bool) $value;
        }

        // Array
        if ($type == 'array') {
            if(is_numeric($value)) {
                return $value;
            } elseif (is_string($value) && tr_is_json($value)) {
                $value = json_decode($value, true);
            } elseif (is_string($value) && is_serialized($value)) {
                $value = unserialize($value);
            } elseif(!is_string($value)) {
                $value = (array) $value;
            }

            return $value;
        }

        // Object
        if ($type == 'object' || $type == 'obj') {
            if(is_numeric($value)) {
                return $value;
            } elseif (is_string($value) && tr_is_json($value)) {
                $value = (object) json_decode($value);
            } elseif (is_string($value) && is_serialized($value)) {
                $value = (object) unserialize($value);
            } elseif(!is_string($value)) {
                $value = (object) $value;
            } elseif (is_array($value)) {
                $value = (object) $value;
            }

            return $value;
        }

        // Callback
        if (is_callable($type)) {
            return call_user_func($type, $value);
        }

        return $value;
    }
}