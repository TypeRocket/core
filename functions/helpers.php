<?php
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
        $model    = "\\" . TR_APP_NAMESPACE . "\\Models\\{$Resource}";
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
     * @param $singular
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
     * @param $resource
     * @param $action
     * @param $title
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
     *
     * @return \TypeRocket\Register\Page
     */
    function tr_resource_pages($singular, $plural = null, array $settings = [])
    {

        if ( ! $plural) {
            $plural = \TypeRocket\Utility\Inflect::pluralize($singular);
        }

        $menu_id = 'add_resource_' . \TypeRocket\Utility\Sanitize::underscore($singular);

        return tr_page($singular, 'index', $plural, $settings)->apply(
            tr_page($singular, 'edit', 'Edit ' . $singular)->useController()->addNewButton()->removeMenu(),
            tr_page($singular, 'show', $singular)->useController()->addNewButton()->removeMenu(),
            tr_page($singular, 'delete', 'Delete ' . $singular)->useController()->removeMenu(),
            tr_page($singular, 'add', 'Add ' . $singular)->useController()->setArgument('menu',
                'Add New')->adminBar($menu_id, $singular, 'new-content')
        )->addNewButton()->useController();
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
     *
     * @return \TypeRocket\Elements\Form
     */
    function tr_form($resource = 'auto', $action = 'update', $item_id = null)
    {
        $form = \TypeRocket\Core\Config::locate('app.class.form');

        return new $form($resource, $action, $item_id);
    }
}

if ( ! function_exists('tr_modify_model_value')) {
    /**
     * Modify Model Value
     *
     * @param \TypeRocket\Models\Model $name use dot notation
     * @param null $arg
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
                $callback_args = $arg2 ? [$arg1] : [];
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

if ( ! function_exists('tr_components_field')) {
    /**
     * Get components
     *
     * Auto binding only for post types
     *
     * @param string $name use dot notation
     * @param null $item_id
     *
     * @param $modelClass
     *
     * @return array|mixed|null|string
     */
    function tr_posts_components_field($name, $item_id = null, $modelClass = \TypeRocket\Models\WPPost::class)
    {
        global $post;

        if (isset($post->ID) && is_null($item_id)) {
            $item_id = $post->ID;
        }

        /** @var \TypeRocket\Models\Model $model */
        $model = new $modelClass;
        $model->findById($item_id);

        $builder_data = $model->getFieldValue($name);

        if (is_array($builder_data)) {
            foreach ($builder_data as $data) {
                $key       = key($data);
                $component = strtolower(key($data));
                $paths     = \TypeRocket\Core\Config::locate('paths');
                $file      = $paths['visuals'] . '/' . $name . '/' . $component . '.php';
                if (file_exists($file)) {
                    $fn = function ($file, $data, $name, $item_id, $model) {
                        /** @noinspection PhpIncludeInspection */
                        include($file);
                    };
                    $fn($file, $data[$key], $name, $item_id, $model);
                } else {
                    echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> Add builder visual here by creating: <code>{$file}</code></div>";
                }
            }
        }

        return $builder_data;
    }
}

if ( ! function_exists('tr_components')) {
    /**
     * Get components
     *
     * Auto binding only for post types
     *
     * @param string $name use dot notation
     * @param null $item_id
     *
     * @param $modelClass
     *
     * @return array|mixed|null|string
     */
    function tr_components_field($name, $item_id = null, $modelClass = \TypeRocket\Models\WPPost::class)
    {
        global $post;

        if (isset($post->ID) && is_null($item_id)) {
            $item_id = $post->ID;
        }

        /** @var \TypeRocket\Models\Model $model */
        $model = new $modelClass;
        $model->findById($item_id);

        $builder_data = $model->getFieldValue($name);
        tr_components_loop($builder_data, compact('name', 'item_id', 'model'));

        return $builder_data;
    }
}

if( ! function_exists('tr_components_loop')) {
    /**
     * Loop Components
     *
     * @param array $builder_data
     * @param array $other be sure to pass $name, $item_is, $model
     */
    function tr_components_loop($builder_data, $other = []) {
        extract($other);
        foreach ($builder_data as $data) {
            $key       = key($data);
            $component = strtolower(key($data));
            $paths     = \TypeRocket\Core\Config::locate('paths');
            $file      = $paths['visuals'] . '/' . $name . '/' . $component . '.php';
            if (file_exists($file)) {
                $fn = function ($file, $data, $name, $item_id, $model) {
                    /** @noinspection PhpIncludeInspection */
                    include($file);
                };
                $fn($file, $data[$key], $name, $item_id, $model);
            } else {
                echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> Add builder visual here by creating: <code>{$file}</code></div>";
            }
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
     * @param $string
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

if ( ! function_exists('tr_valid_compcount')) {
    /**
     * validate component count
     * @param  \TypeRocket\Utility\Validator  $validator
     * @param  array      $value      Component/builder field value array.
     * @param  string     $field      Name of field.
     * @param  array      $options    Validation options.
     * @return array                  Error message.
     */
    function tr_valid_compcount( $validator, $value, $field, $options )
    {
      $rule = new \TypeRocket\Utility\ValidationRule( 'compcount', $value, $field, $options );
      if( $rule->conditionMet() ) return ['error' => $rule->getMessage()];
    }
}

if ( ! function_exists('tr_valid_repcount')) {
    /**
     * validate repeater row count
     * @param  \TypeRocket\Utility\Validator  $validator
     * @param  array      $value      Repeater field value array.
     * @param  string     $field      Name of field.
     * @param  array      $options    Validation options.
     * @return array                  Error message.
     */
    function tr_valid_repcount( $validator, $value, $field, $options )
    {
      $rule = new \TypeRocket\Utility\ValidationRule( 'repcount', $value, $field, $options );
      if( $rule->conditionMet() ) return ['error' => $rule->getMessage()];
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
        return new \TypeRocket\Http\Route();
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
     * @param string $name the constant variable name
     * @param null|mixed $default The default value
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