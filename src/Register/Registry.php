<?php
namespace TypeRocket\Register;

use Closure;
use TypeRocket\Core\Config;
use TypeRocket\Elements\BaseForm;
use TypeRocket\Models\WPPost;
use TypeRocket\Utility\Str;
use WP_Post;
use WP_Query;
use WP_Term;

class Registry
{
    public static $collection = [];
    public static $aggregateCollection = [];

    public static $postTypes = [
        'post' => ['singular' => 'post', 'plural' => 'posts', 'controller' => null, 'object' => null, 'model' => null],
        'page' => ['singular' => 'page', 'plural' => 'pages', 'controller' => null, 'object' => null, 'model' => null],
    ];

    public static $taxonomies = [
        'category' => ['singular' => 'category', 'plural' => 'categories', 'controller' => null, 'object' => null, 'model' => null],
        'post_tag' => ['singular' => 'tag', 'plural' => 'tags', 'controller' => null, 'object' => null, 'model' => null]
    ];

    public static $customs = [];

    /**
     * Add a post type resource
     *
     * @param string $id post type id
     * @param array $resource resource name ex. posts, pages, books
     */
    public static function addPostTypeResource($id, $resource = []) {
        self::$postTypes[$id] = array_pad($resource, 5, null);
    }

    /**
     * Get the post type resource
     *
     * @param string $id
     *
     * @return null
     */
    public static function getPostTypeResource($id) {
        return ! empty(self::$postTypes[$id]) ? self::$postTypes[$id] : null;
    }

    /**
     * Get the taxonomy resource
     *
     * @param string $id
     *
     * @return null
     */
    public static function getTaxonomyResource($id) {
        return ! empty(self::$taxonomies[$id]) ? self::$taxonomies[$id] : null;
    }

    /**
     * Add a taxonomy resource
     *
     * @param string $id post type id
     * @param array $resource resource name ex. posts, pages, books
     */
    public static function addTaxonomyResource($id, $resource = []) {
        self::$taxonomies[$id] = array_pad($resource, 5, null);
    }

    /**
     * Add a custom resource
     *
     * @param string $id custom resource id
     * @param array $resource resource name ex. posts, pages, books
     */
    public static function addCustomResource($id, $resource = []) {
        self::$customs[$id] = array_pad($resource, 3, null);
    }

    /**
     * Get the custom resource
     *
     * @param string $id
     *
     * @return null
     */
    public static function getCustomResource($id) {
        return self::$customs[$id] ?? null;
    }

    /**
     * Add Registrable objects to collection
     *
     * @param null|Registrable|string $obj
     */
    public static function addRegistrable( $obj = null )
    {
        if ( $obj instanceof Registrable) {
            self::$collection[] = $obj;
        }
    }

    /**
     * Loop through each Registrable and add hooks automatically
     */
    public static function initHooks()
    {
        $collection = [];
        $later = [];

        if(empty(self::$collection)) {
            return;
        }

        foreach(self::$collection as $obj) {
            if ( $obj instanceof Registrable) {
                $collection[] = $obj;
                $use = $obj->getApplied();
                foreach($use as $objUsed) {
                    if( ! in_array($objUsed, $collection) && ! $objUsed instanceof Page) {
                        $later[] = $obj;
                        array_pop($collection);
                        break 1;
                    }
                }

                if ($obj instanceof Page && ! empty( $obj->getParent() ) ) {
                    $later[] = $obj;
                    array_pop($collection);
                }
            }
        }
        $collection = array_merge($collection, $later);

        foreach ($collection as $obj) {
            if ($obj instanceof Taxonomy) {
                add_action( 'init', [$obj, 'register']);
            } elseif ($obj instanceof PostType) {
                /** @var PostType $obj */
                add_action( 'init', [$obj, 'register']);
            } elseif ($obj instanceof MetaBox) {
                add_action( 'admin_init', [$obj, 'register']);
                add_action( 'add_meta_boxes', [$obj, 'register']);
            } elseif ($obj instanceof Page) {
                if($obj->getHandler()) {
                    add_action( 'admin_init', [$obj, 'respond']);
                }

                add_action( 'admin_menu', [$obj, 'register']);
            }
        }

        add_action( 'init', function() {
            self::setAggregatePostTypeHooks();
        }, 12);
    }

    /**
     * Taxonomy Hooks
     *
     * @param Taxonomy $obj
     */
    public static function taxonomyHooks(Taxonomy $obj)
    {
        self::taxonomyFormContent($obj);

        if($custom_templates = $obj->getTemplates()) {
            foreach(['taxonomy', 'category', 'tag'] as $template_hook) {
                add_filter($template_hook . '_template', Closure::bind(function($template) use ($custom_templates) {
                    /** @var WP_Term $term */
                    $term = get_queried_object();

                    if($term->taxonomy == $this->getId()) {
                        $template = $custom_templates['archive'];
                    }

                    return $template;
                }, $obj), 0, 1);
            }
        }
    }

    /**
     * Post Type Hooks
     *
     * @param PostType $obj
     */
    public static function postTypeHooks(PostType $obj)
    {
        if (is_string( $obj->getTitlePlaceholder() )) {
            add_filter( 'enter_title_here', function($title) use ($obj) {
                global $post;

                if(!empty($post)) {
                    if ( $post->post_type == $obj->getId() ) {
                        return $obj->getTitlePlaceholder();
                    }
                }

                return $title;

            } );
        }

        if( !empty($obj->getArchiveQuery()) ) {
            add_action('pre_get_posts', Closure::bind(function( WP_Query $main_query ) {
                /**
                 * @var PostType $this
                 */
                if(!$main_query->is_main_query() || $main_query->is_admin) {
                    return;
                }

                $isTax = false;
                $id = $this->getId();

                if($this->getArchiveQueryWithTaxonomies() && !empty($main_query->tax_query->queries)) {
                    $taxonomyList = get_object_taxonomies($id);
                    foreach($taxonomyList as $taxonomy){
                        if($taxonomy == 'category' && $main_query->is_category()) {
                            $isTax = true;
                            break;
                        }
                        elseif($taxonomy == 'post_tag' && $main_query->is_tag()) {
                            $isTax = true;
                            break;
                        }
                        elseif($main_query->is_tax($taxonomy)){
                            $isTax = true;
                            break;
                        }
                    }
                }

                if($main_query->is_post_type_archive($id) || $isTax) {
                    $query = $this->getArchiveQuery();
                    foreach ($query as $key => $value) {
                        $main_query->set($key, $value);
                    }
                }
            }, $obj));
        }

        if($custom_templates = $obj->getTemplates()) {
            foreach(['single', 'archive', 'page'] as $template_hook) {
                if(!empty($custom_templates[$template_hook])) {
                    add_filter($template_hook . '_template', Closure::bind(function($template, $type) use ($custom_templates) {
                        /** @var WP_Post $post */
                        global $post;

                        if($post->post_type == $this->getId()) {
                            $template = $custom_templates[$type];
                        }

                        return $template;
                    }, $obj), 0, 2);
                }
            }
        }

        if(!empty($obj->getSaves())) {
            add_action('save_post', function ($id, $post) use ($obj) {
                if(
                    $post->post_type != $obj->getId() ||
                    wp_is_post_revision($id) ||
                    $post->post_status == 'auto-draft' ||
                    $post->post_status == 'trash'
                ) { return; }

                global $wpdb;
                $saves = $obj->getSaves();
                $class = $obj->getModelClass();
                $model = (new $class)->wpPost($post, true)->load('meta');
                $fields = [];

                foreach ($saves as $field => $fn) {
                    $value = $fn($model);
                    $value = sanitize_post_field($field, $value, $id, 'db' );
                    $fields[$field] = $value;
                }

                $wpdb->update( $wpdb->posts, $fields, ['ID' => $id]);
            }, 11, 2);
        }

        if(!is_null($obj->getRevisions())) {
            add_filter( 'wp_revisions_to_keep', function($num, $post) use ($obj) {
                if ( $post->post_type == $obj->getId() ) {
                    return $obj->getRevisions();
                }

                return $num;
            }, 10, 2 );
        }

        if($obj->getRootSlug()) {
            self::$aggregateCollection['post_type']['root_slug'][] = $obj->getId();
        }

        if($obj->getForceDisableGutenberg()) {
            self::$aggregateCollection['post_type']['use_gutenberg'][] = $obj->getId();
        }

        self::setPostTypeColumns($obj);
        self::postTypeFormContent($obj);
    }

    /**
     * Add taxonomy form hooks
     *
     * @param Taxonomy $obj
     */
    public static function taxonomyFormContent( Taxonomy $obj ) {

        $callback = function( $term, $obj )
        {
            /** @var Taxonomy $obj */
            if ( $term == $obj->getId() || $term->taxonomy == $obj->getId() ) {
                $func = 'add_form_content_' . $obj->getId() . '_taxonomy';

                $form = $obj->getMainForm();
                if (is_callable( $form )) {
                    call_user_func( $form, $term );
                } elseif (function_exists( $func )) {
                    call_user_func( $func, $term );
                } elseif ( Config::get('app.debug') ) {
                    echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon dashicons dashicons-editor-code\"></i> Add content here by defining: <code>function {$func}() {}</code></div>";
                }
            }
        };

        if ($obj->getMainForm()) {
            add_action( $obj->getId() . '_edit_form', function($term) use ($obj, $callback) {
                echo BaseForm::nonceInput('hook');
                echo '<div class="tr-taxonomy-edit-container typerocket-wp-style-table">';
                call_user_func_array($callback, [$term, $obj]);
                echo '</div>';
            }, 10, 2 );

            add_action( $obj->getId() . '_add_form_fields', function($term) use ($obj, $callback) {
                echo BaseForm::nonceInput('hook');
                echo '<div class="tr-taxonomy-add-container typerocket-wp-style-subtle">';
                call_user_func_array($callback, [$term, $obj]);
                echo '</div>';
            }, 10, 2 );
        }
    }

    /**
     * Add post type form hooks
     *
     * @param PostType $obj
     */
    public static function postTypeFormContent( PostType $obj) {

        /**
         * @param WP_Post $post
         * @param string $type
         * @param PostType $obj
         */
        $callback = function( $post, $type, $obj )
        {
            if ($post->post_type == $obj->getId()) {
                $func = 'add_form_content_' . $obj->getId() . '_' . $type;
                echo '<div class="typerocket-container">';
                echo BaseForm::nonceInput('hook');

                $form = $obj->getForm( $type );
                if (is_callable( $form )) {
                    call_user_func( $form );
                } elseif (function_exists( $func )) {
                    call_user_func( $func, $post );
                } elseif (Config::get('app.debug')) {
                    echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon dashicons dashicons-editor-code\"></i> Add content here by defining: <code>function {$func}() {}</code></div>";
                }
                echo '</div>';
            }
        };

        // edit_form_top
        if ($obj->getForm( 'top' )) {
            add_action( 'edit_form_top', function($post) use ($obj, $callback) {
                $type = 'top';
                call_user_func_array($callback, [$post, $type, $obj]);
            } );
        }

        // edit_form_after_title
        if ($obj->getForm( 'title' )) {
            add_action( 'edit_form_after_title', function($post) use ($obj, $callback) {
                $type = 'title';
                call_user_func_array($callback, [$post, $type, $obj]);
            } );
        }

        // edit_form_after_editor
        if ($obj->getForm( 'editor' )) {
            add_action( 'edit_form_after_editor', function($post) use ($obj, $callback) {
                $type = 'editor';
                call_user_func_array($callback, [$post, $type, $obj]);
            } );
        }

        // dbx_post_sidebar
        if ($obj->getForm( 'bottom' )) {
            add_action( 'dbx_post_sidebar', function($post) use ($obj, $callback) {
                $type = 'bottom';
                call_user_func_array($callback, [$post, $type, $obj]);
            } );
        }

    }

    /**
     * Add post type admin table columns hooks
     *
     * @param PostType $post_type
     */
    public static function setPostTypeColumns( PostType $post_type)
    {
        $pt = $post_type->getId();
        $new_columns = $post_type->getColumns();
	    $primary_column = $post_type->getPrimaryColumn();
        $model = $post_type->getModelClass();

        add_filter( "manage_edit-{$pt}_columns" , function($columns) use ($new_columns) {
            foreach ($new_columns as $key => $new_column) {
                if($new_column == false && array_key_exists($key, $columns)) {
                    unset($columns[$key]);
                } else {
                    $columns[$new_column['field']] = $new_column['label'];
                }
            }

            return $columns;
        });

        add_action( "manage_{$pt}_posts_custom_column" , function($column, $post_id) use ($new_columns, $model) {
            $post = get_post($post_id);

            /** @var WPPost $post_temp */
            $post_temp = (new $model)->wpPost($post, true)->load('meta');

            foreach ($new_columns as $new_column) {
                if(!empty($new_column['field']) && $column == $new_column['field']) {

                    $data = [
                        'column' => $column,
                        'field' => $new_column['field'],
                        'post' => $post,
                        'post_id' => $post_id,
                        'model' => $post_temp
                    ];

                    $value = $post_temp
                        ->setProperty($post_temp->getIdColumn(), $post_id)
                        ->getFieldValue($new_column['field']);

                    if($result = call_user_func_array($new_column['callback'], [$value, $data])) {
                        echo $result;
                    }
                }
            }
        }, 10, 2);

	    if( $primary_column ) {
		    add_filter( 'list_table_primary_column', function ( $default, $screen ) use ( $pt, $primary_column ) {

			    if ( $screen === 'edit-' . $pt ){
				    $default = $primary_column;
			    }

			    return $default;
		    }, 10, 2 );
	    }

        foreach ($new_columns as $new_column) {
            // Only meta fields can be sortable
            if(!empty($new_column['sort']) && !Str::contains('.', $new_column['field'])) {
                add_filter( "manage_edit-{$pt}_sortable_columns", function($columns) use ($new_column) {
                    $columns[$new_column['field']] = $new_column['field'];
                    return $columns;
                } );

                add_action( 'load-edit.php', function() use ($pt, $new_column) {
                    add_filter( 'request', function( $vars ) use ($pt, $new_column) {
                        if ( isset( $vars['post_type'] ) && $pt == $vars['post_type'] ) {
                            if ( isset( $vars['orderby'] ) && $new_column['field'] == $vars['orderby'] ) {

                                if( ! in_array($new_column['field'], (new WPPost())->getBuiltinFields())) {
                                    if( is_string($new_column['order_by']) ) {
                                        switch($new_column['order_by']) {
                                            case 'number':
                                            case 'num':
                                            case 'int':
                                                $new_vars['orderby'] = 'meta_value_num';
                                                break;
                                            case 'decimal':
                                            case 'double':
                                                $new_vars['orderby'] = 'meta_value_decimal';
                                                break;
                                            case 'date':
                                                $new_vars['orderby'] = 'meta_value_date';
                                                break;
                                            case 'datetime':
                                                $new_vars['orderby'] = 'meta_value_datetime';
                                                break;
                                            case 'time':
                                                $new_vars['orderby'] = 'meta_value_time';
                                                break;
                                            case 'string':
                                            case 'str':
                                                break;
                                            default:
                                                $new_vars['orderby'] = $new_column['order_by'];
                                                break;
                                        }
                                    }
                                    $new_vars['meta_key'] = $new_column['field'];
                                } else {
                                    $new_vars = [ 'orderby' => $new_column['field'] ];
                                }

                                $vars = array_merge( $vars, $new_vars );
                            }
                        }

                        return $vars;
                    });
                } );
            }
        }
    }

    /**
     * Run aggregate
     */
    public static function setAggregatePostTypeHooks()
    {
        global $wp_rewrite;

        /**
         * Post Type Hooks
         */
        $use_gutenberg = self::$aggregateCollection['post_type']['use_gutenberg'] ?? null;

        if($use_gutenberg) {
            add_filter('use_block_editor_for_post_type', function ($current_status, $post_type) use ($use_gutenberg) {
                if (in_array($post_type, $use_gutenberg)) return false;
                return $current_status;
            }, 10, 2);
        }

        $root_slugs = self::$aggregateCollection['post_type']['root_slug'] ?? null;

        if($root_slugs) {
            add_filter( 'post_type_link', function ( $post_link, $post ) use ($root_slugs) {
                if ( in_array($post->post_type, $root_slugs) ) {
                    $post_link = str_replace( '?' . $post->post_type . '=', '', $post_link );
                }
                return $post_link;
            }, 10, 2 );

            add_action( 'pre_get_posts', function ( $query ) use ($root_slugs) {
                global $wpdb;

                /** @var WP_Query $query */
                if ( ! $query->is_main_query() || empty( $query->query['name'] ) ) {
                    return;
                }

                if (! isset($query->query['feed']) && ( ! isset( $query->query['page'] ) || 2 !== count( $query->query ) ) ) {
                    return;
                }

                $types = "'" . implode("','", $root_slugs) . "'";
                $check_sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ({$types}) AND post_name = %s AND post_status IN ('publish','private') LIMIT 1";

                if( $wpdb->get_var( $wpdb->prepare( $check_sql, $query->query['name'] ) ) ) {
                    $query->set( 'post_type', $root_slugs );
                }

            });

            add_filter('wp_unique_post_slug', function($slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug) use ($root_slugs) {
                global $wpdb, $wp_rewrite;

                $post_types = array_merge(['post', 'page'], $root_slugs);
                $types = "'" . implode("','", $post_types) . "'";

                if ( in_array($post_type, $post_types) || in_array( $slug, $wp_rewrite->feeds ) || 'embed' === $slug || apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type ) ) {
                    $suffix = 2;
                    $check_sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_type IN ({$types}) AND post_name = %s AND ID != %d LIMIT 1";
                    do {
                        $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_ID ) );
                        $alt_post_name   = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";

                        if($post_name_check) {
                            $slug = $alt_post_name;
                        }

                        $suffix++;
                    } while ( $post_name_check );
                }

                return $slug;

            }, 0, 6);
        }
    }
}
