<?php
namespace TypeRocket\Register;

use Exception;
use TypeRocket\Auth\Roles;
use TypeRocket\Models\WPPost;
use TypeRocket\Utility\Inflect;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Str;
use WP_Post_Type;

class PostType extends Registrable
{
    use Resourceful;

    protected $title = null;
    protected $form = [];
    /** @var callable[]  */
    protected $saves = [];
    protected $modelClass = WPPost::class;
    protected $taxonomies = [];
    protected $revisions = null;
    protected $columns = [];
    protected $primaryColumn = null;
    protected $metaBoxes = [];
    protected $archiveQuery = [];
    protected $archiveQueryTaxonomies = false;
    protected $icon = null;
    protected $existing = null;
    protected $hooksAttached = false;
    protected $rootSlug = false;
    protected $featureless = false;
    protected $forceDisableGutenberg = false;
    protected $maxIdLength = 20;

    /**
     * Make or Modify Post Type.
     *
     * Do not use before init hook.
     *
     * @param string $singular singular name is required
     * @param string|array|null $plural plural name or settings array override
     * @param array|null $settings args override and extend
     * @param string|null $id post type ID
     */
    public function __construct( $singular, $plural = null, $settings = null, $id = null )
    {
        $singularLower = strtolower( trim($singular) );
        $id = $id ?? $singularLower;

        if(is_array($plural) && is_null($settings)) {
            $settings = $plural;
            $plural = null;
        }

        if(is_null($settings)) {
            $settings = [];
        }

        if(empty($plural)) {
            $plural = trim(strtolower(Inflect::pluralize($singular)));
        }

        $labelSingular = $singular;
        $labelPlural = $plural;
        $keep_case = false;

        if(!empty($settings['labeled'])) {
            $labelSingular = $settings['labeled'][0] ?? $labelSingular;
            $labelPlural = $settings['labeled'][1] ?? $labelPlural;
            $keep_case = $settings['labeled'][2] ?? $keep_case;
            unset($settings['labeled']);
        }

        if(empty($settings['labeled'])) {
            $this->applyQuickLabels($labelSingular, $labelPlural, $keep_case);
        }

        $this->existing = get_post_type_object($id);

        if($this->existing) {
            $this->id = $this->existing->name;
            $args = (array) $this->existing;

            $singular = Sanitize::underscore( $singular );
            $plural  = Sanitize::underscore( $plural );

            // obj is set on registration
            $this->resource = Registry::getPostTypeResource($this->id) ?? [
                'singular' => $singular,
                'plural' => $plural,
                'model' => null,
                'controller' => null
            ];

            $args['supports'] = array_keys(get_all_post_type_supports($this->id));
            $this->args = array_merge($args, $this->args, $settings);

            return $this;
        }

        // setup object for later use
        $plural   = Sanitize::underscore( $plural );
        $singular = Sanitize::underscore( $singular );
        $this->resource = [
            'singular' => $singular,
            'plural' => $plural,
            'model' => null,
            'controller' => null
        ];

        $this->setId( $this->id ?: ($id ?? $singular) );

        if (array_key_exists( 'capabilities', $settings ) && $settings['capabilities'] === true) :
            $settings['capabilities'] = (new Roles)->getCustomPostTypeCapabilities($singular, $plural);
        endif;

        $defaults = [
            'description' => $plural,
            'rewrite'     => [ 'slug' => Sanitize::dash( $plural ) ],
            'public'      => true,
            'supports'    => [ 'title', 'editor' ],
            'has_archive' => true,
            'show_in_rest' => false,
            'taxonomies'  => [ ]
        ];

        if (array_key_exists( 'taxonomies', $settings )) {
            $this->taxonomies       = array_merge( $this->taxonomies, $settings['taxonomies'] );
            $settings['taxonomies'] = $this->taxonomies;
        }

        $this->args = array_merge( $this->args, $defaults, $settings );

        if(class_exists( $model = \TypeRocket\Utility\Helper::modelClass($singular, false) ) ) {
            $this->setModelClass($model);
        }

        return $this;
    }

    /**
     * Set Model Class
     *
     * @param string $modelClass
     *
     * @return $this
     */
    public function setModelClass(string $modelClass)
    {
        $this->modelClass = $modelClass;

        // Default resource model is not the same as the modelClass
        $this->resource['model'] = $this->modelClass;

        return $this;
    }

    /**
     * Get Model Class
     *
     * @return string
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }

    /**
     * Use Custom Capabilities
     *
     * @return PostType $this
     */
    public function customCapabilities()
    {
        $cap = (new Roles)->getCustomPostTypeCapabilities($this->resource['singular'], $this->resource['plural']);
        return $this->setArgument('capabilities', $cap);
    }

    /**
     * Apply Quick Labels
     *
     * @link https://developer.wordpress.org/reference/functions/get_post_type_labels/
     *
     * @param string $singular
     * @param string|null $plural
     * @param bool $keep_case
     * @return PostType $this
     */
    public function applyQuickLabels($singular, $plural = null, $keep_case = false)
    {
        if(!$plural) { $plural = Inflect::pluralize($singular); }

        $upperSingular = $keep_case ? $singular : Str::uppercaseWords( $singular );
        $lowerSingular = $keep_case ? $singular : mb_strtolower( $singular );
        $upperPlural   = $keep_case ? $plural : Str::uppercaseWords( $plural );
        $lowerPlural   = $keep_case ? $plural : mb_strtolower( $plural );

        $context = 'post_type:' . $this->getId();

        $labels = [
            'add_new'               => _x('Add New', $context, 'typerocket-core'),
            'all_items'             => sprintf( _x('All %s', $context, 'typerocket-core'), $upperPlural),
            'archives'              => sprintf( _x('%s Archives', $context, 'typerocket-core'), $upperSingular),
            'add_new_item'          => sprintf( _x('Add New %s', $context, 'typerocket-core'), $upperSingular),
            'attributes'            => sprintf( _x('%s Attributes', $context, 'typerocket-core'), $upperSingular),
            'edit_item'             => sprintf( _x('Edit %s', $context, 'typerocket-core'), $upperSingular),
            'filter_items_list'     => sprintf( _x('Filter %s list %s', $context, 'typerocket-core'), $lowerPlural, $upperSingular),
            'insert_into_item'      => sprintf( _x('Insert into %s', $context, 'typerocket-core'), $lowerSingular),
            'item_published'        => sprintf( _x('%s published.', $context, 'typerocket-core'), $upperSingular),
            'item_published_privately' => sprintf( _x('%s published privately.', 'typerocket-core'), $upperSingular),
            'item_updated'          => sprintf( _x('%s updated.', $context, 'typerocket-core'), $upperSingular),
            'item_reverted_to_draft'=> sprintf( _x('%s reverted to draft.', $context, 'typerocket-core'), $upperSingular),
            'item_scheduled'        => sprintf( _x('%s scheduled.', $context, 'typerocket-core'), $upperSingular),
            'items_list'            => sprintf( _x('%s list', $context, 'typerocket-core'), $upperPlural),
            'menu_name'             => sprintf( _x('%s',  $context . ':admin menu', 'typerocket-core'), $upperPlural),
            'name'                  => sprintf( _x('%s', $context . ':post type general name', 'typerocket-core'), $upperPlural),
            'name_admin_bar'        => sprintf( _x('%s', $context . ':add new from admin bar', 'typerocket-core'), $upperSingular),
            'items_list_navigation' => sprintf( _x('%s list navigation', $context, 'typerocket-core'), $upperPlural),
            'new_item'              => sprintf( _x('New %s', $context, 'typerocket-core'), $upperSingular),
            'not_found'             => sprintf( _x('No %s found', $context, 'typerocket-core'), $lowerPlural),
            'not_found_in_trash'    => sprintf( _x('No %s found in Trash', $context, 'typerocket-core'), $lowerPlural),
            'parent_item_colon'     => sprintf( _x("Parent %s:", $context, 'typerocket-core'), $upperPlural),
            'search_items'          => sprintf( _x('Search %s', $context, 'typerocket-core'), $upperPlural),
            'singular_name'         => sprintf( _x('%s',  $context . ':post type singular name', 'typerocket-core'), $upperSingular),
            'uploaded_to_this_item' => sprintf( _x('Uploaded to this %s', $context, 'typerocket-core'), $lowerSingular),
            'view_item'             => sprintf( _x('View %s', $context, 'typerocket-core'), $upperSingular),
            'view_items'            => sprintf( _x('View %s', $context, 'typerocket-core'), $upperPlural),
        ];

        return $this->setLabels($labels, $upperPlural, false);
    }

    /**
     * Set Labels
     *
     * @param array $labels
     * @param string $plural
     * @param bool $merge
     *
     * @return PostType $this
     */
    public function setLabels(array $labels, $plural = null, $merge = true)
    {
        $this->args['labels'] = $merge ? array_merge($this->args['labels'] ?? [], $labels) : $labels;
        $this->args['label'] = $plural ?? $this->args['label'];

        return $this;
    }

    /**
     * Get Existing Post Type
     *
     * @return WP_Post_Type|null
     */
    public function getExisting()
    {
        return $this->existing;
    }

    /**
     * Set the post type menu icon
     *
     * @link https://developer.wordpress.org/resource/dashicons/
     *
     * @param string $name icon name does not require prefix.
     *
     * @return PostType $this
     */
    public function setIcon( $name )
    {
        $this->setArgument('menu_icon', 'dashicons-' . Str::trimStart($name, 'dashicons-'));

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function saveTitleAs(callable $callback)
    {
        $this->saves['post_title'] = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function saveContentAs(callable $callback)
    {
        $this->saves['post_content'] = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function saveMenuOrderAs(callable $callback)
    {
        $this->saves['menu_order'] = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function saveExcerptAs(callable $callback)
    {
        $this->saves['post_excerpt'] = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function savePostNameAs(callable $callback)
    {
        $this->saves['post_name'] = $callback;

        return $this;
    }

    /**
     * @param string $field
     * @param callable $callback
     *
     * @return $this
     */
    public function saveFieldAs(string $field,callable $callback)
    {
        $this->saves[$field] = $callback;

        return $this;
    }

    /**
     * Get Saver
     *
     * @param string|null $key
     *
     * @return callable|null|array
     */
    public function getSaves($key = null)
    {
        return is_null($key) ? $this->saves : ($this->saves[$key] ?? null);
    }

    /**
     * Get the post type icon
     *
     * @return null|string
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * Set Hierarchical
     *
     * @param bool $bool
     *
     * @return PostType $this
     */
    public function setHierarchical($bool = true)
    {
        return $this->setArgument('hierarchical', $bool);
    }

    /**
     * Exclude from search
     *
     * @param bool $bool
     *
     * @return PostType
     */
    public function excludeFromSearch($bool = true)
    {
        return $this->setArgument('exclude_from_search', $bool);
    }

    /**
     * Delete with user
     *
     * Whether to delete posts of this type when deleting a user. If true, posts of this type
     * belonging to the user will be moved to trash when then user is deleted. If false,
     * posts of this type belonging to the user will not be trashed or deleted.
     *
     * @param bool $bool
     *
     * @return PostType
     */
    public function deleteWithUser($bool = true)
    {
        return $this->setArgument('delete_with_user', $bool);
    }

    /**
     * Set Position
     *
     * @param int $number range 5 - 100 and default is 25
     *
     * @return PostType
     */
    public function setPosition($number = 25)
    {
        return $this->setArgument('menu_position', $number);
    }

    /**
     * Get the placeholder title
     *
     * @return null|string
     */
    public function getTitlePlaceholder()
    {
        return $this->title;
    }

    /**
     * Set the placeholder title for the title field
     *
     * @param string $text
     *
     * @return PostType $this
     */
    public function setTitlePlaceholder( $text )
    {
        $this->title = (string) $text;

        return $this;
    }

    /**
     * Get the form hook value by key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getForm( $key )
    {
        $form = null;
        if(array_key_exists($key, $this->form)) {
            $form = $this->form[$key];
        }

        return $form;
    }

    /**
     * Set the form title hook
     *
     * From hook to be added just below the title field
     *
     * @param bool|true|callable $value
     *
     * @return PostType $this
     */
    public function setTitleForm( $value = true )
    {

        if (is_callable( $value )) {
            $this->form['title'] = $value;
        } else {
            $this->form['title'] = true;
        }

        return $this;
    }

    /**
     * Set the form top hook
     *
     * From hook to be added just above the title field
     *
     * @param bool|true|callable $value
     *
     * @return PostType $this
     */
    public function setTopForm( $value = true )
    {
        if (is_callable( $value )) {
            $this->form['top'] = $value;
        } else {
            $this->form['top'] = true;
        }

        return $this;
    }

    /**
     * Set the from bottom hook
     *
     * From hook to be added below the meta boxes
     *
     * @param bool|true|callable $value
     *
     * @return PostType $this
     */
    public function setBottomForm( $value = true )
    {
        if (is_callable( $value )) {
            $this->form['bottom'] = $value;
        } else {
            $this->form['bottom'] = true;
        }

        return $this;
    }

    /**
     * Set the form editor hook
     *
     * From hook to be added below the editor
     *
     * @param bool|true|callable $value
     *
     * @return PostType $this
     */
    public function setMainForm($value = true)
    {
        return $this->setEditorForm($value);
    }

    /**
     * Set the form editor hook
     *
     * From hook to be added below the editor
     *
     * @param bool|true|callable $value
     *
     * @return PostType $this
     */
    public function setEditorForm( $value = true )
    {
        if (is_callable( $value )) {
            $this->form['editor'] = $value;
        } else {
            $this->form['editor'] = true;
        }

        return $this;
    }

    /**
     * Set Supports
     *
     * Options include: 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks',
     * 'custom-fields', 'comments', 'revisions', 'page-attributes', 'post-formats'
     *
     * @param array $args
     *
     * @return PostType
     */
    public function setSupports(array $args)
    {
        $this->args['supports'] = $args;

        return $this;
    }

    /**
     * Add Support
     *
     * Options include: 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks',
     * 'custom-fields', 'comments', 'revisions', 'page-attributes', 'post-formats'
     *
     * @param string $type support option
     * @return $this
     */
    public function addSupport($type)
    {
        $this->args['supports'][] = $type;
        $this->args['supports'] = array_unique($this->args['supports']);

        return $this;
    }

    /**
     * Get Supports
     *
     * @return array|bool
     */
    public function getSupports()
    {
        return $this->args['supports'];
    }

    /**
     * Make Featureless
     *
     * Removes all base features from post type excluding custom meta boxes
     *
     * @return $this
     */
    public function featureless()
    {
        $this->featureless = true;
        $this->setSupports([]);

        return $this;
    }

    /**
     * Keep Number of Revisions
     *
     * @param int $number
     *
     * @return $this
     */
    public function setRevisions($number)
    {
        $this->revisions = (int) $number;

        return $this;
    }

    /**
     * Get Revisions
     *
     * @return int|null
     */
    public function getRevisions()
    {
        return $this->revisions;
    }

    /**
     * Set the rewrite slug for the post type
     *
     * @param string $slug
     * @param null|bool $withFront
     *
     * @return PostType $this
     */
    public function setSlug( $slug, $withFront = null )
    {
        if(!is_array($this->args['rewrite'])) {
            $this->args['rewrite'] = [];
        }

        $this->args['rewrite']['slug'] = Sanitize::dash( $slug );

        if(isset($withFront)) {
            $this->args['rewrite']['with_front'] = $withFront;
        }

        return $this;
    }

    /**
     * Disable Slug With Front
     *
     * @return $this
     */
    public function disableSlugWithFront()
    {
        $this->args['rewrite']['with_front'] = false;

        return $this;
    }

    /**
     * Get the rewrite slug
     *
     * @return mixed
     */
    public function getSlug()
    {
        return $this->args['rewrite']['slug'];
    }

    /**
     * Get Root Slug
     *
     * @return bool
     */
    public function getRootSlug()
    {
        return $this->rootSlug;
    }

    /**
     * @param bool|string $rest_base the REST API base path
     * @param null|string $controller the REST controller default is \WP_REST_Posts_Controller::class
     * @return PostType $this
     */
    public function setRest( $rest_base = false, $controller = null )
    {
        $this->args['rest_base'] = $rest_base ? $rest_base : $this->id;
        $this->args['show_in_rest'] = true;
        $controller ? $this->args['rest_controller_class'] = $controller : null;

        return $this;
    }

    /**
     * Disable the Archive Page
     *
     * @return PostType $this
     */
    public function disableArchivePage()
    {
        $this->args['has_archive'] = false;

        return $this;
    }

    /**
     * Enable Gutenberg
     *
     * @return PostType
     */
    public function enableGutenberg()
    {
        $this->forceDisableGutenberg = false;
        return $this->addSupport('editor')->setArgument('show_in_rest', true);
    }

    /**
     * Force Disable Gutenberg
     *
     * @return PostType
     */
    public function forceDisableGutenberg()
    {
        $this->forceDisableGutenberg = true;

        return $this;
    }

    /**
     * Get Force Disable Gutenberg
     *
     * @return bool
     */
    public function getForceDisableGutenberg()
    {
        return $this->forceDisableGutenberg;
    }

    /**
     * Apply Archive Query to All Taxonomy Archives
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function setArchiveQueryWithTaxonomies($bool = true)
    {
        $this->archiveQueryTaxonomies = (bool) $bool;

        return $this;
    }

    /**
     * Get Archive Query Taxonomies
     *
     * @return bool
     */
    public function getArchiveQueryWithTaxonomies()
    {
        return $this->archiveQueryTaxonomies;
    }

    /**
     * Change The Main Archive Page Query
     *
     * @param array $query the query modifiers
     *
     * @return PostType $this
     */
    public function setArchiveQuery( array $query )
    {
        $this->archiveQuery = $query;

        return $this;
    }

    /**
     * Set Archive Query Key
     *
     * @param string $key
     * @param mixed $value
     *
     * @return PostType $this
     */
    public function setArchiveQueryKey($key, $value)
    {
        $this->archiveQuery[$key] = $value;

        return $this;
    }

    /**
     * Get Archive Query
     *
     * @return array
     */
    public function getArchiveQuery()
    {
        return $this->archiveQuery;
    }

    /**
     * Remove Archive Query Key
     *
     * @param string $key
     *
     * @return PostType $this
     */
    public function removeArchiveQueryKey($key)
    {
        if (array_key_exists($key, $this->args)) {
            unset($this->archiveQuery[$key]);
        }

        return $this;
    }

    /**
     * Show Number Of Items On Archive Page
     *
     * @param int $number
     *
     * @return PostType $this
     */
    public function setArchivePostsPerPage($number = -1)
    {
        $this->archiveQuery['posts_per_page'] = (int) $number;

        return $this;
    }

    /**
     * Add Column To Admin Table
     *
     * @param string|null $field the name of the field
     * @param bool|string|callable $sort_by_c make column sortable (doubles as order_by when string) | callable override
     * @param string|null $label the label for the table header
     * @param callable|null $callback the function used to display the field data
     *
     * @return PostType $this
     */
    public function addColumn($field, $sort_by_c = false, $label = null, $callback = null) {
        if( ! $label ) {
            $name_parts = explode('.', strrev($field), 2);
            $label = ucwords(strrev($name_parts[0]));
            $field = Sanitize::underscore( $field, true );
        }
        $field = Sanitize::underscore( $field, true );
        if( ! $callback ) {
            $callback = function($value) {
                return $value;
            };
        }

        if(is_callable($sort_by_c)) {
            $callback = $sort_by_c;
            $sort_by_c = false;
        }

        $this->columns[$field] = [
            'field' => $field,
            'sort' => $sort_by_c ? true : false,
            'label' => $label,
            'callback' => $callback,
            'order_by' => $sort_by_c
        ];

        return $this;
    }

    /**
     * Remove Column
     *
     * @param string $field
     *
     * @return PostType $this
     */
    public function removeColumn($field)
    {
        $this->columns[$field] = false;

        return $this;
    }

    /**
     * Get Admin Page Table Columns
     *
     * @return array
     */
    public function getColumns() {
        return $this->columns;
    }

	/**
	 * Set Primary Column that will contain the "Edit | Quick Edit | Trash | View" controls
	 *
	 * @param string $field
	 *
	 * @return PostType $this
	 */
    public function setPrimaryColumn( $field ) {
    	$this->primaryColumn = $field;

    	return $this;
    }

	/**
	 * Get Primary Column
	 *
	 * @return null
	 */
    public function getPrimaryColumn() {
    	return $this->primaryColumn;
    }

    /**
     * Set the post type to only show in WordPress Admin
     *
     * @return PostType $this
     */
    public function setAdminOnly() {
        $this->args['public'] = false;
        $this->args['has_archive'] = false;
        $this->args['show_ui'] = true;
        $this->args['show_in_nav_menus'] = true;

        return $this;
    }

    /**
     * Hide Admin
     *
     * @return $this
     */
    public function hideAdmin()
    {
        $this->args['show_ui'] = false;
        $this->args['show_in_nav_menus'] = false;
        $this->args['show_in_admin_bar'] = false;
        $this->args['show_in_menu'] = false;

        return $this;
    }

    /**
     * Hide Frontend
     *
     * @return $this
     */
    public function hideFrontend()
    {
        $this->args['publicly_queryable'] = false;
        $this->args['exclude_from_search'] = false;

        return $this;
    }

    /**
     * Set As Root
     *
     * This will make the post type use the root URL for
     * single posts and disable the archive page.
     *
     * @return PostType
     */
    public function setRootOnly()
    {
        $this->setArgument('publicly_queryable', true);
        $this->setArgument('query_var', true);
        $this->setArgument('rewrite', false);
        $this->disableArchivePage();
        $this->rootSlug = true;

        return $this;
    }

    /**
     * Register post type with WordPress
     *
     * Use the registered_post_type hook if you need to update
     * the post type.
     *
     * @return PostType $this
     */
    public function register()
    {
        if(!$this->existing) {
            if($this->isReservedId()) {
                return $this;
            }
        }

        $supports = array_filter(array_unique(array_merge($this->args['supports'] ?: [], $this->metaBoxes)));
        $this->args['supports'] = $this->featureless && empty($supports) ? false : $supports;
        do_action('typerocket_post_type_register_' . $this->id, $this);
        register_post_type( $this->id, $this->args );
        $this->resource['object'] = $this;
        Registry::addPostTypeResource($this->id, $this->resource);
        $this->attachHooks();

        return $this;
    }

    /**
     * Add meta box to post type
     *
     * @param string|array|MetaBox $s
     *
     * @return PostType $this
     */
    public function addMetaBox( $s )
    {
        if ( $s instanceof MetaBox ) {
            $s = (string) $s->getId();
        } elseif( is_array($s) ) {
            foreach($s as $n) {
                $this->addMetaBox($n);
            }

            return $this;
        }

        if(is_string($s)) {
            $this->metaBoxes[] = $s;
        }

        return $this;
    }

    /**
     * Add taxonomy to post type
     *
     * @param string|Taxonomy|array $s
     *
     * @return PostType $this
     */
    public function addTaxonomy( $s )
    {

        if ( $s instanceof Taxonomy) {
            $s = (string) $s->getId();
        } elseif( is_array($s) ) {
            foreach($s as $n) {
                $this->addTaxonomy($n);
            }
        }

        if ( is_string($s) && ! in_array( $s, $this->taxonomies )) {
            $this->taxonomies[]       = $s;
            $this->args['taxonomies'] = $this->taxonomies;
        }

        return $this;
    }

    /**
     * Attach Hooks
     */
    public function attachHooks()
    {
        if(!$this->hooksAttached) {
            Registry::postTypeHooks($this);
            $this->hooksAttached = true;
        }
    }

}
