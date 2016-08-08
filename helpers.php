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

    return tr_page($plural, 'index', $plural, $settings)->apply(
        tr_page($plural, 'edit', 'Edit ' . $singular)->useController()->addNewButton()->removeMenu(),
        tr_page($plural, 'show', $singular)->useController()->addNewButton()->removeMenu(),
        tr_page($plural, 'delete', 'Delete ' . $singular)->useController()->removeMenu(),
        tr_page($plural, 'add', 'Add ' . $singular)->useController()->setArgument('menu', 'Add New')
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
 * @param $model
 *
 * @return \TypeRocket\Elements\Tables
 */
function tr_tables(\TypeRocket\Models\SchemaModel $model)
{
    return new \TypeRocket\Elements\Tables($model);
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

    $model = new \TypeRocket\Models\PostTypesModel();
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
function tr_components_field($name, $item_id = null, $modelClass = \TypeRocket\Models\PostTypesModel::class )
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

    $model = tr_get_model('Users');
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
    $model = tr_get_model('Options');

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

    $model = tr_get_model('Comments');
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
    /** @var \TypeRocket\Models\TaxonomiesModel $model */
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
    /** @var \TypeRocket\Models\TaxonomiesModel $model */
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

if( ! function_exists('tr_redirect') ) {
/**
 * @return \TypeRocket\Http\Redirect
 */
function tr_redirect()
{
    return new \TypeRocket\Http\Redirect();
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
    return substr($subject, 0, strlen($needle) ) === $needle;
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
    $length = mb_strlen($needle);
    if ($length == 0) {
        return true;
    }

    return ( mb_substr($subject, -$length ) === $needle );
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
    return ( mb_strpos( $subject, $needle ) !== false );
}
}