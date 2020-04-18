<?php
namespace TypeRocket\Register;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Icons;
use TypeRocket\Utility\Inflect;
use TypeRocket\Utility\Sanitize;

class PostType extends Registrable
{
    use Resourceful;

    protected $title = null;
    protected $form = [];
    protected $taxonomies = [];
    protected $columns = [];
    protected $primaryColumn = null;
    protected $metaBoxes = [];
    protected $archiveQuery = [];
    protected $icon = null;
    protected $resource = null;
    protected $existing = null;
    protected $hooksAttached = false;
    protected $rootSlug = false;
    protected $forceDisableGutenberg = false;

    /**
     * Make or Modify Post Type.
     *
     * Do not use before init hook.
     *
     * @param string $singular singular name is required
     * @param string $plural plural name
     * @param array $settings args override and extend
     */
    public function __construct( $singular, $plural = null, $settings = [] )
    {
        // make lowercase
        $singularLower = strtolower( trim($singular) );

        if(is_null($plural)) {
            $plural = strtolower(Inflect::pluralize($singular));
            $this->existing = get_post_type_object($singularLower);

            if($this->existing) {
                $this->id = $this->existing->name;
                $this->args = (array) $this->existing;

                $singular = Sanitize::underscore( $singular );
                $plural  = Sanitize::underscore( $plural );

                $this->resource = Registry::getPostTypeResource($this->id) ?? [$singular, $plural, null, null];
                $this->args['supports'] = array_keys(get_all_post_type_supports($this->id));
                $this->args = array_merge($this->args, $settings);

                return $this;
            }
        }

        $this->applyQuickLabels($singular, $plural);

        // setup object for later use
        $plural   = Sanitize::underscore( $plural );
        $singular = Sanitize::underscore( $singular );
        $this->resource = [$singular, $plural, $this->modelClass, $this->controllerClass];
        $this->id       = ! $this->id ? $singular : $this->id;

        if (array_key_exists( 'capabilities', $settings ) && $settings['capabilities'] === true) :
            $settings['capabilities'] = [
                'publish_posts'       => 'publish_' . $plural,
                'edit_post'           => 'edit_' . $singular,
                'edit_posts'          => 'edit_' . $plural,
                'edit_others_posts'   => 'edit_others_' . $plural,
                'delete_post'         => 'delete_' . $singular,
                'delete_posts'        => 'delete_' . $plural,
                'delete_others_posts' => 'delete_others_' . $plural,
                'read_post'           => 'read_' . $singular,
                'read_private_posts'  => 'read_private_' . $plural,
            ];
        endif;

        $defaults = [
            'description' => $plural,
            'rewrite'     => [ 'slug' => Sanitize::dash( $this->id ) ],
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

        return $this;
    }

    /**
     * Apply Quick Labels
     *
     * @link https://developer.wordpress.org/reference/functions/get_post_type_labels/
     *
     * @param string $singular
     * @param string $plural
     * @param bool $keep_case
     * @return PostType $this
     */
    public function applyQuickLabels($singular, $plural = null, $keep_case = false)
    {
        if(!$plural) { $plural = Inflect::pluralize($singular); }

        // make lowercase
        $upperSingular = $keep_case ? $singular : ucwords( $singular );
        $upperPlural   = $keep_case ? $plural : ucwords( $plural );
        $pluralLower   = $keep_case ? $plural : strtolower( $plural );

        $labels = [
            'add_new'            => __('Add New', 'typerocket-profile'),
            'all_items'          => __('All ' . $upperPlural, 'typerocket-profile'),
            'add_new_item'       => __('Add New ' . $upperSingular, 'typerocket-profile'),
            'edit_item'          => __('Edit ' . $upperSingular, 'typerocket-profile'),
            'item_published'     => __($upperSingular . ' published.', 'typerocket-profile'),
            'item_updated'       => __($upperSingular . ' updated.', 'typerocket-profile'),
            'item_reverted_to_draft' => __($upperSingular . ' reverted to draft.', 'typerocket-profile'),
            'item_scheduled'     => __($upperSingular . ' scheduled.', 'typerocket-profile'),
            'menu_name'          => __($upperPlural, 'typerocket-profile'),
            'name'               => __($upperPlural, 'typerocket-profile'),
            'new_item'           => __('New ' . $upperSingular, 'typerocket-profile'),
            'not_found'          => __('No ' . $pluralLower . ' found', 'typerocket-profile'),
            'not_found_in_trash' => __('No ' . $pluralLower . ' found in Trash', 'typerocket-profile'),
            'parent_item_colon'  => __('Parent ' . $upperSingular . ':', 'typerocket-profile'),
            'search_items'       => __('Search ' . $upperPlural, 'typerocket-profile'),
            'singular_name'      => __($upperSingular, 'typerocket-profile'),
            'view_item'          => __('View ' . $upperSingular, 'typerocket-profile'),
        ];

        $this->args['label'] = $upperPlural;
        $this->args['labels'] = $labels;

        return $this;
    }

    /**
     * Get Existing Post Type
     *
     * @return \WP_Post_Type|null
     */
    public function getExisting()
    {
        return $this->existing;
    }

    /**
     * Set the post type menu icon
     *
     * Add the CSS needed to create the icon for the menu
     *
     * @param string $name
     *
     * @return PostType $this
     */
    public function setIcon( $name )
    {
        $name       = strtolower( $name );
        $icons      = Config::locate('app.class.icons');
        $icons      = new $icons;

        $this->icon = !empty($icons[$name]) ? $icons[$name] : null;
        if( ! $this->icon ) {
            return $this;
        }

        add_action( 'admin_head', \Closure::bind( function() use ($icons) {
            $postType = $this->getId();
            $icon = $this->getIcon();
            $id = in_array($postType, ['post', 'page']) ? "#menu-{$postType}s" : "#menu-posts-{$postType}";
            echo "
            <style type=\"text/css\">
                #adminmenu {$id} .wp-menu-image:before {
                    font: {$icons->fontWeight} {$icons->fontSize} {$icons->fontFamily} !important;
                    content: '{$icon}';
                    speak: none;
                    top: 2px;
                    position: relative;
                    -webkit-font-smoothing: antialiased;
                }
            </style>";
        }, $this) );

        return $this;
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
     * Set the rewrite slug for the post type
     *
     * @param string $slug
     *
     * @return PostType $this
     */
    public function setSlug( $slug )
    {
        $this->args['rewrite'] = ['slug' => Sanitize::dash( $slug )];

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
    public function getForgeDisableGutenberg()
    {
        return $this->forceDisableGutenberg;
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
     * @param string $value
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
    public function setArchivePostsPerPage( $number = -1)
    {
        $this->archiveQuery['posts_per_page'] = $number;

        return $this;
    }

    /**
     * Add Column To Admin Table
     *
     * @param string|null $field the name of the field
     * @param bool $sort make column sortable
     * @param string|null $label the label for the table header
     * @param callable|null $callback the function used to display the field data
     * @param string $order_by is the column a string or number
     *
     * @return PostType $this
     */
    public function addColumn($field, $sort = false, $label = null, $callback = null, $order_by = '') {
        if( ! $label ) { $label = $field; }
        $field = Sanitize::underscore( $field );
        if( ! $callback ) {
            $callback = function($value) {
                echo $value;
            };
        }

        $this->columns[$field] = [
            'field' => $field,
            'sort' => $sort,
            'label' => $label,
            'callback' => $callback,
            'order_by' => $order_by
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
            $this->dieIfReserved();
        }

        $supports = array_unique(array_merge($this->args['supports'], $this->metaBoxes));
        $this->args['supports'] = $supports;
        do_action('tr_post_type_register_' . $this->id, $this);
        register_post_type( $this->id, $this->args );
        Registry::addPostTypeResource($this->id, $this->resource);
        $this->attachHooks();

        return $this;
    }

    /**
     * Add meta box to post type
     *
     * @param string|MetaBox $s
     *
     * @return PostType $this
     */
    public function addMetaBox( $s )
    {
        if ( $s instanceof MetaBox ) {
            $s = (string) $s->getId();
        }elseif( is_array($s) ) {
            foreach($s as $n) {
                $this->addMetaBox($n);
            }
        }

        $this->metaBoxes[] = $s;

        return $this;
    }

    /**
     * Add taxonomy to post type
     *
     * @param string|Taxonomy $s
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

        if ( ! in_array( $s, $this->taxonomies )) {
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
