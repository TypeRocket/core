<?php
namespace TypeRocket\Register;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Icons;
use TypeRocket\Utility\Inflect;
use TypeRocket\Utility\Sanitize;

class PostType extends Registrable
{
    use Resourceful;

    private $title = null;
    private $form = [];
    private $taxonomies = [];
    private $columns = [];
    private $metaBoxes = [];
    private $archiveQuery = [];
    private $icon = null;
    private $resource = null;

    /**
     * Make Post Type. Do not use before init hook.
     *
     * @param string $singular singular name is required
     * @param string $plural plural name
     * @param array $settings args override and extend
     */
    public function __construct( $singular, $plural = null, $settings = [] )
    {

        if(is_null($plural)) {
            $plural = Inflect::pluralize($singular);
        }

        // make lowercase
        $upperSingular = ucwords( $singular );
        $upperPlural   = ucwords( $plural );
        $singular      = strtolower( $singular );
        $plural        = strtolower( $plural );

        $labels = [
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New ' . $upperSingular,
            'edit_item'          => 'Edit ' . $upperSingular,
            'menu_name'          => $upperPlural,
            'name'               => $upperPlural,
            'new_item'           => 'New ' . $upperSingular,
            'not_found'          => 'No ' . $plural . ' found',
            'not_found_in_trash' => 'No ' . $plural . ' found in Trash',
            'parent_item_colon'  => '',
            'search_items'       => 'Search ' . $upperPlural,
            'singular_name'      => $upperSingular,
            'view_item'          => 'View ' . $upperSingular,
        ];

        // setup object for later use
        $plural   = Sanitize::underscore( $plural );
        $singular = Sanitize::underscore( $singular );
        $this->resource = [$singular, $plural];
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
            'labels'      => $labels,
            'description' => $plural,
            'rewrite'     => [ 'slug' => Sanitize::dash( $this->id ) ],
            'public'      => true,
            'supports'    => [ 'title', 'editor' ],
            'has_archive' => true,
            'taxonomies'  => [ ]
        ];

        if (array_key_exists( 'taxonomies', $settings )) {
            $this->taxonomies       = array_merge( $this->taxonomies, $settings['taxonomies'] );
            $settings['taxonomies'] = $this->taxonomies;
        }

        $this->args = array_merge( $defaults, $settings );

        return $this;
    }

    /**
     * Set the post type menu icon
     *
     * Add the CSS needed to create the icon for the menu
     *
     * @param $name
     *
     * @return $this
     */
    public function setIcon( $name )
    {
        $name       = strtolower( $name );
        $icons      = Config::getIcons();

        if( ! $icons instanceof Icons ) {
            $icons = new Icons();
        }

        $this->icon = !empty($icons[$name]) ? $icons[$name] : null;
        if( ! $this->icon ) {
            return $this;
        }

        add_action( 'admin_head', \Closure::bind( function() use ($icons) {
            $postType = $this->getId();
            $icon = $this->getIcon();
            echo "
            <style type=\"text/css\">
                #adminmenu #menu-posts-{$postType} .wp-menu-image:before {
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
     * @return null
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * Get the placeholder title
     *
     * @return null
     */
    public function getTitlePlaceholder()
    {
        return $this->title;
    }

    /**
     * Set the placeholder title for the title field
     *
     * @param $text
     *
     * @return $this
     */
    public function setTitlePlaceholder( $text )
    {
        $this->title = (string) $text;

        return $this;
    }

    /**
     * Get the form hook value by key
     *
     * @param $key
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * Set the rewrite slug for the post type
     *
     * @param $slug
     *
     * @return $this
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
     * @param bool|string $rest_base the REST API base path
     *
     * @return $this
     */
    public function setRest( $rest_base = false )
    {
        $this->args['rest_base'] = $rest_base ? $rest_base : $this->id;
        $this->args['show_in_rest'] = true;

        return $this;
    }

    /**
     * Change The Main Archive Page Query
     *
     * @param array $query the query modifiers
     *
     * @return $this
     */
    public function setArchiveQuery( array $query )
    {
        $this->archiveQuery = $query;

        return $this;
    }

    /**
     * Set Archive Query Key
     *
     * @param $key
     * @param $value
     *
     * @return $this
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
     * @param $key
     *
     * @return $this
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
     * @return $this
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
     * @param callback|null $callback the function used to display the field data
     * @param string $order_by is the column a string or number
     *
     * @return $this
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
     * @param $field
     *
     * @return $this
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
     * Set the post type to only show in WordPress Admin
     *
     * @return $this
     */
    public function setAdminOnly() {
        $this->args['public'] = false;
        $this->args['has_archive'] = false;
        $this->args['show_ui'] = true;

        return $this;
    }

    /**
     * Register post type with WordPress
     *
     * Use the registered_post_type hook if you need to update
     * the post type.
     *
     * @return $this
     */
    public function register()
    {
        $this->dieIfReserved();

        $supports = array_unique(array_merge($this->args['supports'], $this->metaBoxes));
        $this->args['supports'] = $supports;

        register_post_type( $this->id, $this->args );
        Registry::addPostTypeResource($this->id, $this->resource);
        return $this;
    }

    /**
     * Add meta box to post type
     *
     * @param string|MetaBox $s
     *
     * @return $this
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
     * @return $this
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

}
