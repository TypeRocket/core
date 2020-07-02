<?php
if( ! function_exists('tr_app') ) {
    /**
     * Get Namespaced Class
     *
     * @param string $append
     * @return string
     */
    function tr_app( $append ) {
        $space = "\\" . TR_APP_NAMESPACE . "\\";
        return $space . $append;
    }
}

if( ! function_exists('tr_response') ) {
    /**
     * Get Main Response
     *
     * @return \TypeRocket\Http\Response
     */
    function tr_response() {
        return \TypeRocket\Core\Injector::findOrNewSingleton(\TypeRocket\Http\Response::class);
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
        $root = \TypeRocket\Core\Config::locate('paths.urls.assets');
        return $root . '/' . ltrim($append, '/');
    }
}


if( ! function_exists('tr_views_dir') ) {
    /**
     * Get Views Directory
     *
     * @param string $append
     * @return string
     */
    function tr_views_dir( $append ) {
        $root = \TypeRocket\Core\Config::locate('paths.views');
        return $root . '/' . ltrim($append, '/');
    }
}

if ( ! function_exists('tr_get_model')) {
    /**
     * Get model by recourse
     *
     * @param string $resource use the resource name to get model
     *
     * @return null|\TypeRocket\Models\Model $object
     */
    function tr_get_model($resource)
    {
        $Resource = TypeRocket\Utility\Str::camelize($resource);
        $model    = tr_app("Models\\{$Resource}");
        $object   = null;

        if (class_exists($model)) {
            /** @var \TypeRocket\Models\Model $object */
            $object = new $model;
        }

        return $object;
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
     * @param null $name
     * @param null $screen
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
     *
     * @return \TypeRocket\Register\Page
     */
    function tr_page($resource, $action, $title, array $settings = [])
    {
        $obj = new \TypeRocket\Register\Page($resource, $action, $title, $settings);
        $obj->addToRegistry();

        return $obj;
    }
}

if ( ! function_exists('tr_resource_pages')) {
    /**
     * @param string $singular
     * @param string $plural
     * @param array $settings
     * @param null $resource
     * @return \TypeRocket\Register\Page
     */
    function tr_resource_pages($singular, $plural = null, array $settings = [], $resource = null)
    {
        list($singular, $handle) = array_pad(explode('@', $singular), 2, null);

        if ( ! $plural) {
            $plural = \TypeRocket\Utility\Inflect::pluralize($singular);
        }

        if( ! $resource) {
            $resource = $singular;
        }

        $menu_id = 'add_resource_' . \TypeRocket\Utility\Sanitize::underscore($singular);

        $add = tr_page($resource, 'add', 'Add ' . $singular)
            ->setArgument('menu', __('Add New', 'typerocket-domain'))
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

        $edit = tr_page($resource, 'edit', 'Edit ' . $singular)
            ->addNewButton()
            ->removeMenu()
            ->mapActions([
                'GET' => 'edit',
                'PUT' => 'update',
            ]);

        $index = tr_page($resource, 'index', $plural, $settings)
            ->apply($edit, $show, $delete, $add)
            ->addNewButton();

        foreach ([$add, $edit, $delete, $show, $index] as $page) {
            /** @var \TypeRocket\Register\Page $page */
            $page->useController();

            if($handle) {
                $page->setHandler($handle);
            }
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

if ( ! function_exists('tr_tables')) {
    /**
     * Create tables
     *
     * @param int $limit
     * @param \TypeRocket\Models\Model $model
     *
     * @return \TypeRocket\Elements\Tables
     */
    function tr_tables($limit = 25, $model = null)
    {
        return new \TypeRocket\Elements\Tables($limit, $model);
    }
}

if ( ! function_exists('tr_buffer')) {
    /**
     * Create buffer
     *
     * @return \TypeRocket\Utility\Buffer
     */
    function tr_buffer()
    {
        return new \TypeRocket\Utility\Buffer();
    }
}

if ( ! function_exists('tr_form')) {
    /**
     * Instance the From
     *
     * @param string $resource posts, users, comments, options your own
     * @param string $action update or create
     * @param null|int $item_id you can set this to null or an integer
     * @param null|Model|string $model
     * @return \TypeRocket\Elements\Form
     */
    function tr_form($resource = 'auto', $action = 'update', $item_id = null, $model = null)
    {
        $form = \TypeRocket\Core\Config::locate('app.class.form');

        return new $form($resource, $action, $item_id, $model);
    }
}

if ( ! function_exists('tr_modify_model_value')) {
    /**
     * Modify Model Value
     *
     * @param \TypeRocket\Models\Model $model use dot notation
     * @param mixed $args
     *
     * @return array|mixed|null|string
     */
    function tr_modify_model_value($model, $args)
    {
        if(!empty($args[0]) && $args[0] != ':' && !is_array($args)) {
            return $model->getFieldValue($args);
        }

        if(is_array($args)) {
            $value = $model->getFieldValue($args['name']);
            $callback_args = array_merge([$value], $args['args']);
            return call_user_func_array($args['callback'], $callback_args);
        }

        list($modifier, $arg1, $arg2) = array_pad(explode(':', ltrim($args, ':'), 3), 3, null);
        $name = $arg2 ? $arg2 : $arg1 ;
        $value = $model->getFieldValue($name);

        switch($modifier) {
            case 'img';
                $size = $arg2 ? $arg1 : 'thumbnail' ;
                $modified = wp_get_attachment_image( (int) $value, $size);
                break;
            case 'img_src';
                $size = $arg2 ? $arg1 : 'thumbnail' ;
                $modified = wp_get_attachment_image_src( (int) $value, $size);
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

if ( ! function_exists('tr_posts_field')) {
    /**
     * Get the posts field
     *
     * @param string $name use dot notation
     * @param null $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_posts_field($name, $item_id = null)
    {
        global $post;

        if (isset($post->ID) && is_null($item_id)) {
            $item_id = $post->ID;
        }

        $model = new \TypeRocket\Models\WPPost();
        $model->findById($item_id);

        return tr_modify_model_value($model, $name);
    }
}

if ( ! function_exists('tr_posts_components_field')) {
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
     * @deprecated
     * @return array|mixed|null|string
     */
    function tr_posts_components_field($name, $item_id = null, $modelClass = null)
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

        if (is_array($builder_data)) {
            $i = 0;
            $len = count($builder_data);
            foreach ($builder_data as $hash => $data) {
                $first_item = $last_item = false;

                if ($i == 0) {
                    $first_item = true;
                } else if ($i == $len - 1) {
                    $last_item = true;
                }

                $key       = key($data);
                $component = strtolower(key($data));
                $paths     = \TypeRocket\Core\Config::locate('paths');
                $file      = $paths['visuals'] . '/' . $name . '/' . $component . '.php';
                if (file_exists($file)) {
                    $fn = function ($file, $data, $name, $item_id, $model, $first_item, $last_item, $component_id, $hash) {
                        /** @noinspection PhpIncludeInspection */
                        include($file);
                    };
                    $fn($file, $data[$key], $name, $item_id, $model, $first_item, $last_item, $key, $hash);
                } else {
                    echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> Add builder visual here by creating: <code>{$file}</code></div>";
                }
                $i++;
            }
        }

        return $builder_data;
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

if( ! function_exists('tr_get_post_type_model') ) {
    /**
     * Get Model Assigned To WP_Post Class
     *
     * @param WP_Post $wp_post
     * @param null|string $override
     * @return \TypeRocket\Models\WPPost
     */
    function tr_get_post_type_model(WP_Post $wp_post, $override = null) {

        if($override) { return new $override; }

        $resource_data = \TypeRocket\Register\Registry::getPostTypeResource($wp_post->post_type);
        $Resource = \TypeRocket\Utility\Str::camelize($resource_data[0] ?? '');
        $model = $resource_data[2] ?? tr_app("Models\\{$Resource}");

        return class_exists($model) ? new $model: new \TypeRocket\Models\WPPost($wp_post->post_type);
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
         */
        extract($other);
        $i = 0;
        $len = count($builder_data);
        foreach ($builder_data as $hash => $data) {
            $first_item = $last_item = false;

            if ($i == 0) {
                $first_item = true;
            } else if ($i == $len - 1) {
                $last_item = true;
            }

            $key       = key($data);
            $component = strtolower(key($data));
            $paths     = \TypeRocket\Core\Config::locate('paths');
            $file      = $paths['visuals'] . '/' . $name . '/' . $component . '.php';
            $file = apply_filters('tr_component_file', $file, ['folder' => $name, 'name' => $component, 'view' => 'visual']);
            if (file_exists($file)) {
                $fn = function ($file, $data, $name, $item_id, $model, $first_item, $last_item, $component_id, $hash) {
                    /** @noinspection PhpIncludeInspection */
                    include($file);
                };
                $fn($file, $data[$key], $name, $item_id, $model, $first_item, $last_item, $key, $hash);
            } else {
                echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> Add builder visual here by creating: <code>{$file}</code></div>";
            }

            $i++;
        }
    }
}

if ( ! function_exists('tr_users_field')) {
    /**
     * Get users field
     *
     * @param string $name use dot notation
     * @param null $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_users_field($name, $item_id = null)
    {
        global $user_id, $post;

        if (isset($user_id) && is_null($item_id)) {
            $item_id = $user_id;
        } elseif (is_null($item_id) && isset($post->ID)) {
            $item_id = get_the_author_meta('ID');
        } elseif (is_null($item_id)) {
            $item_id = get_current_user_id();
        }

        $model = tr_get_model('User');
        $model->findById($item_id);

        return tr_modify_model_value($model, $name);
    }
}

if ( ! function_exists('tr_options_field')) {
    /**
     * Get options
     *
     * @param string $name use dot notation
     *
     * @return array|mixed|null|string
     */
    function tr_options_field($name)
    {
        $model = tr_get_model('Option');

        return tr_modify_model_value($model, $name);
    }
}

if ( ! function_exists('tr_comments_field')) {
    /**
     * Get comments field
     *
     * @param string $name use dot notation
     * @param null $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_comments_field($name, $item_id = null)
    {
        global $comment;

        if (isset($comment->comment_ID) && is_null($item_id)) {
            $item_id = $comment->comment_ID;
        }

        $model = tr_get_model('Comment');
        $model->findById($item_id);

        return tr_modify_model_value($model, $name);
    }
}

if ( ! function_exists('tr_taxonomies_field')) {
    /**
     *  Get taxonomy field
     *
     * @param string $name use dot notation
     * @param string $taxonomy taxonomy id
     * @param null|int $item_id
     *
     * @return array|mixed|null|string
     */
    function tr_taxonomies_field($name, $taxonomy, $item_id = null)
    {
        /** @var \TypeRocket\Models\WPTerm $model */
        $model = tr_get_model($taxonomy);
        $model->findById($item_id);

        return tr_modify_model_value($model, $name);
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
        $model = tr_get_model($resource);
        $model->findById($item_id);

        return tr_modify_model_value($model, $name);
    }
}

if ( ! function_exists('tr_is_json')) {
    /**
     * Detect is JSON
     *
     * @param string $string
     *
     * @return bool
     */
    function tr_is_json($string)
    {
        $j = json_decode($string);
        $r = $j ? true : false;

        return $r;
    }
}

if ( ! function_exists('tr_is_frontend')) {
    /**
     * Check if the frontend is being used
     */
    function tr_is_frontend()
    {
        return !is_admin();
    }
}

if ( ! function_exists('tr_ssl')) {
    /**
     * SSL
     */
    function tr_ssl()
    {
        return new TypeRocket\Http\SSL();
    }
}

if ( ! function_exists('tr_image_sizing')) {
    /**
     * SSL
     */
    function tr_image_sizing()
    {
        return new \TypeRocket\Utility\ImageSizer();
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

if ( ! function_exists('tr_redirect_data')) {
    /**
     * @param null $default
     * @param bool $delete
     *
     * @return \TypeRocket\Http\Redirect
     */
    function tr_redirect_data($default = null, $delete = true)
    {
        $data = (new \TypeRocket\Http\Cookie())->getTransient('tr_redirect_data', $delete);

        return ! is_null($data) ? $data : $default;
    }
}

if ( ! function_exists('tr_nonce_field')) {
    /**
     * TypeRocket Nonce Field
     */
    function tr_nonce_field()
    {
        return wp_nonce_field('form_' . \TypeRocket\Core\Config::locate('app.seed'), '_tr_nonce_form', false, false);
    }
}

if ( ! function_exists('tr_rest_field')) {
    /**
     * TypeRocket Nonce Field
     *
     * @param string $method GET, POST, PUT, PATCH, DELETE
     *
     * @return string
     */
    function tr_rest_field($method = 'POST')
    {
        return "<input type=\"hidden\" name=\"_method\" value=\"{$method}\"  />";
    }
}

if ( ! function_exists('tr_hidden_form_fields')) {
    /**
     * TypeRocket Nonce Field
     *
     * @param string $method GET, POST, PUT, PATCH, DELETE
     *
     * @return string
     */
    function tr_hidden_form_fields($method = 'POST')
    {
        return tr_rest_field($method) . tr_nonce_field();
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
        $data = (new \TypeRocket\Http\Cookie())->getTransient('tr_old_fields', $delete);

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
        $data = (new \TypeRocket\Http\Cookie())->getTransient('tr_old_fields', $delete);

        return ! is_null($data) ? $data : $default;
    }
}

if ( ! function_exists('tr_old_fields_remove')) {
    /**
     * @return bool
     */
    function tr_old_fields_remove()
    {
        (new \TypeRocket\Http\Cookie())->getTransient('tr_old_fields', true);

        return ! (bool)(new \TypeRocket\Http\Cookie())->getTransient('tr_old_fields');
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

if ( ! function_exists('tr_view')) {
    /**
     * @param string $dots
     * @param array $data
     *
     * @return \TypeRocket\Template\View
     */
    function tr_view($dots, array $data = [])
    {
        return new \TypeRocket\Template\View($dots, $data);
    }
}

if ( ! function_exists('tr_validator')) {
    /**
     * Validate fields
     *
     * @param array $options
     * @param array $fields
     * @param null $modelClass
     *
     * @return \TypeRocket\Utility\Validator
     */
    function tr_validator($options, $fields, $modelClass = null)
    {
        return new \TypeRocket\Utility\Validator($options, $fields, $modelClass);
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
        return \TypeRocket\Core\Injector::resolve(\TypeRocket\Http\RouteCollection::class);
    }
}

if ( ! function_exists('tr_route_lookup')) {
    /**
     * Get Routes Repo
     * @param string $name
     * @return null|\TypeRocket\Http\Route
     */
    function tr_route_lookup($name)
    {
        return tr_routes_repo()->getNamedRoute($name);
    }
}

if ( ! function_exists('tr_route_url_lookup')) {
    /**
     * Get Routes Repo
     * @param string $name
     * @param array $values
     * @param bool $site
     *
     * @return null|string
     */
    function tr_route_url_lookup($name, $values = [], $site = true)
    {
        return tr_route_lookup($name)->buildUrlFromPattern($values, $site);
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

if ( ! function_exists('tr_http_response')) {
    /**
     * Http Response
     *
     * @param string $returned the constant variable name
     * @param null|\TypeRocket\Http\Response $response The default value
     *
     * @return mixed
     */
    function tr_http_response($returned, $response = null) {

        if($response) {
            status_header( $response->getStatus() );
        }

        if( $returned && empty($_POST['_tr_ajax_request']) ) {

            if( $returned instanceof \TypeRocket\Http\Redirect ) {
                $returned->now();
            }

            if( $returned instanceof \TypeRocket\Template\View ) {
                \TypeRocket\Template\View::load();
            }

            if( is_string($returned) ) {
                echo $returned;
                die();
            }

            \TypeRocket\Http\Routes::resultsToJson( $returned );

        } elseif(!empty($response)) {
            wp_send_json( $response->getResponseArray() );
        } else {
            echo 'Typerocket Response Object required';
            die();
        }
    }
}

if ( ! function_exists('tr_resolve')) {
    /**
     * Automatically Resolve Class
     *
     * Inject all class dependencies
     *
     * @param string $class_name
     * @return object
     * @throws Exception
     */
    function tr_resolve($class_name) {
        return (new \TypeRocket\Core\Resolver())->resolve($class_name);
    }
}

if ( ! function_exists('tr_container')) {
    /**
     * Resolve Class From DI Container
     *
     * Inject all class dependencies
     *
     * @param string $class_name or alias
     * @return object|null
     */
    function tr_container($class_name) {
        return \TypeRocket\Core\Injector::resolve($class_name);
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

if ( ! function_exists('tr_get_url')) {
    /**
     * URL
     *
     * @param string $path relative path to type's location
     * @param string $type theme, mu, or root
     * @return string
     */
    function tr_get_url($path, $type = 'theme') {
        switch ($type) {
            case 'mu' :
            case 'mu-plugins' :
                return WPMU_PLUGIN_URL . '/' . ltrim($path, '/');
            case 'home' :
                return home_url($path);
        }

        return get_theme_file_uri($path);
    }
}

if ( ! function_exists('tr_asset_version')) {
    /**
     * Get Asset Version
     *
     * @param string $fallback version number
     * @param string|null $path file path to asset-version.json
     * @return string
     */
    function tr_asset_version($fallback = '0.0.0', $path = null) {
        $version = json_decode(file_get_contents($path ?? TR_PATH . '/asset-version.json'), true);
        return $version['version'] ?? $fallback;
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
        return \TypeRocket\Core\Config::locate($dots, $default);
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

