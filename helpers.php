<?php
if( ! function_exists('tr_get_model') ) {
/**
 * Get model by recourse
 *
 * @param string $resource use the resource name to get model
 *
 * @return null|\TypeRocket\Models\Model $object
 */
function tr_get_model($resource)
{
    $Resource = ucfirst($resource);
    $model = "\\" . TR_APP_NAMESPACE . "\\Models\\{$Resource}";
    $object   = null;

    if (class_exists($model)) {
        /** @var \TypeRocket\Models\Model $object */
        $object = new $model;
    }

    return $object;
}
}

if( ! function_exists('tr_taxonomy') ) {
/**
 * Register taxonomy
 *
 * @param $singular
 * @param null $plural
 * @param array $settings
 *
 * @return \TypeRocket\Register\Taxonomy
 */
function tr_taxonomy(
    $singular,
    $plural = null,
    $settings = []
) {
    $obj = new \TypeRocket\Register\Taxonomy($singular, $plural, $settings);
    $obj->addToRegistry();

    return $obj;
}
}

if( ! function_exists('tr_post_type') ) {
/**
 * Register post type
 *
 * @param string $singular Singular name for post type
 * @param string|null $plural Plural name for post type
 * @param array $settings The settings for the post type
 *
 * @return \TypeRocket\Register\PostType
 */
function tr_post_type(
    $singular,
    $plural = null,
    $settings = []
) {
    $obj = new \TypeRocket\Register\PostType($singular, $plural, $settings);
    $obj->addToRegistry();

    return $obj;
}
}

if( ! function_exists('tr_meta_box') ) {
/**
 * Register meta box
 *
 * @param null $name
 * @param null $screen
 * @param array $settings
 *
 * @return \TypeRocket\Register\MetaBox
 */
function tr_meta_box(
    $name = null,
    $screen = null,
    $settings = []
) {
    $obj = new \TypeRocket\Register\MetaBox($name, $screen, $settings);
    $obj->addToRegistry();

    return $obj;
}
}

if( ! function_exists('tr_page') ) {
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

if( ! function_exists('tr_resource_pages') ) {
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
        tr_page($singular, 'add', 'Add ' . $singular)->useController()->setArgument('menu', 'Add New')->adminBar($menu_id, $singular, 'new-content' )
    )->addNewButton()->useController();
}
}

if( ! function_exists('tr_tabs') ) {
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

if( ! function_exists('tr_tables') ) {
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

if( ! function_exists('tr_buffer') ) {
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

if( ! function_exists('tr_form') ) {
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
    if( $form = \TypeRocket\Core\Config::locate('app.form') ) {
        return new $form($resource, $action, $item_id);
    }

    return new \TypeRocket\Elements\Form($resource, $action, $item_id);
}
}

if( ! function_exists('tr_posts_field') ) {
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

    return $model->getFieldValue($name);
}
}

if( ! function_exists('tr_components_field') ) {
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
function tr_components_field($name, $item_id = null, $modelClass = \TypeRocket\Models\WPPost::class )
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
            $paths = \TypeRocket\Core\Config::getPaths();
            $file  = $paths['visuals'] . '/' . $name . '/' . $component . '.php';
            if( file_exists($file) ) {
                $fn = function( $file, $data, $name, $item_id, $model) {
                    /** @noinspection PhpIncludeInspection */
                    include( $file );
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

if( ! function_exists('tr_users_field') ) {
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

    return $model->getFieldValue($name);
}
}

if( ! function_exists('tr_options_field') ) {
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

    return $model->getFieldValue($name);
}
}

if( ! function_exists('tr_comments_field') ) {
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

    return $model->getFieldValue($name);
}
}

if( ! function_exists('tr_taxonomies_field') ) {
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

    return $model->getFieldValue($name);
}
}

if( ! function_exists('tr_resource_field') ) {
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

    return $model->getFieldValue($name);
}
}

if( ! function_exists('tr_is_json') ) {
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

if( ! function_exists('tr_frontend') ) {
/**
 * Enable TypeRocket on the front end of the website
 */
function tr_frontend()
{
    $core = new TypeRocket\Core\Launcher();
    $core->initFrontEnd();
}
}

if( ! function_exists('tr_ssl') ) {
    /**
     * SSL
     */
    function tr_ssl()
    {
        return new TypeRocket\Http\SSL();
    }
}

if( ! function_exists('tr_redirect') ) {
/**
 * @return \TypeRocket\Http\Redirect
 */
function tr_redirect()
{
    return new \TypeRocket\Http\Redirect();
}
}

if( ! function_exists('tr_redirect_data') ) {
    /**
     * @param null $default
     * @param bool $delete
     *
     * @return \TypeRocket\Http\Redirect
     */
    function tr_redirect_data($default = null, $delete = true)
    {
        $data = (new \TypeRocket\Http\Cookie())->getTransient('tr_redirect_data', $delete);
        return !is_null($data) ? $data : $default;
    }
}

if( ! function_exists('tr_nonce_field') ) {
/**
 * TypeRocket Nonce Field
 */
function tr_nonce_field() {
    return wp_nonce_field( 'form_' .  \TypeRocket\Core\Config::getSeed() , '_tr_nonce_form', false, false );
}
}

if( ! function_exists('tr_rest_field') ) {
/**
 * TypeRocket Nonce Field
 *
 * @param string $method GET, POST, PUT, PATCH, DELETE
 *
 * @return string
 */
function tr_rest_field($method = 'POST') {
    return "<input type=\"hidden\" name=\"_method\" value=\"{$method}\"  />";
}
}

if( ! function_exists('tr_hidden_form_fields') ) {
/**
 * TypeRocket Nonce Field
 *
 * @param string $method GET, POST, PUT, PATCH, DELETE
 *
 * @return string
 */
function tr_hidden_form_fields($method = 'POST') {
    return tr_rest_field($method) . tr_nonce_field();
}
}

if( ! function_exists('tr_old_field') ) {
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

if( ! function_exists('tr_old_fields') ) {
/**
 * @param null $default
 * @param bool $delete
 *
 * @return string
 */
function tr_old_fields($default = null, $delete = true)
{
    $data = (new \TypeRocket\Http\Cookie())->getTransient('tr_old_fields', $delete);
    return !is_null($data) ? $data : $default;
}
}

if( ! function_exists('tr_old_fields_remove') ) {
/**
 * @return bool
 */
function tr_old_fields_remove()
{
    (new \TypeRocket\Http\Cookie())->getTransient('tr_old_fields', true);
    return ! (bool) (new \TypeRocket\Http\Cookie())->getTransient('tr_old_fields');
}
}

if( ! function_exists('tr_cookie') ) {
/**
 * @return \TypeRocket\Http\Cookie
 */
function tr_cookie()
{
    return new \TypeRocket\Http\Cookie();
}
}

if( ! function_exists('tr_view') ) {
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

if( ! function_exists('tr_validator') ) {
/**
 * Validate fields
 *
 * @param array $options
 * @param array $fields
 * @param null $modelClass
 *
 * @return \TypeRocket\Utility\Validator
 */
function tr_validator( $options, $fields, $modelClass = null )
{
    return new \TypeRocket\Utility\Validator( $options, $fields, $modelClass );
}
}

if( ! function_exists('tr_route') ) {
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

if( ! function_exists('str_starts') ) {
/**
 * String starts with
 *
 * @param $needle
 * @param $subject
 *
 * @return bool
 */
function str_starts( $needle, $subject ) {
    return \TypeRocket\Utility\Str::starts($needle, $subject);
}
}


if( ! function_exists('str_ends') ) {
/**
 * String ends with
 *
 * @param $needle
 * @param $subject
 *
 * @return bool
 */
function str_ends( $needle, $subject ) {
    return \TypeRocket\Utility\Str::ends($needle, $subject);
}
}

if( ! function_exists('str_contains') ) {
/**
 * String ends with
 *
 * @param $needle
 * @param $subject
 *
 * @return bool
 */
function str_contains( $needle, $subject ) {
    return \TypeRocket\Utility\Str::contains($needle, $subject);
}
}

if( ! function_exists('dd') ) {
    /**
     * Die and Dump Vars
     *
     * @param $param
     */
    function dd($param) {
        \TypeRocket\Utility\Debug::dd(func_get_args());
    }
}

if( ! function_exists('tr_query') ) {
    /**
     * Database Query
     *
     * @return \TypeRocket\Database\Query
     */
    function tr_query() {
        return new \TypeRocket\Database\Query();
    }
}