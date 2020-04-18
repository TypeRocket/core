<?php
namespace TypeRocket\Register;

use TypeRocket\Utility\Inflect;
use TypeRocket\Utility\Sanitize;

/**
 * Taxonomy
 *
 * API for http://codex.wordpress.org/Function_Reference/register_taxonomy
 */
class Taxonomy extends Registrable
{
    use Resourceful;

    protected $postTypes = [];
    protected $form = [];
    protected $resource = null;
    protected $existing = null;
    protected $hooksAttached = false;

    /**
     * Make Taxonomy. Do not use before init.
     *
     * @param string $singular singular name is required
     * @param string $plural plural name
     * @param array $settings args override and extend
     */
    public function __construct( $singular, $plural = null, $settings = [])
    {
        $lowerSingular = strtolower( trim($singular) );

        if (is_null( $plural )) {
            $plural = Inflect::pluralize($singular);
            $existing = get_taxonomy( strtolower($lowerSingular) );

            if($existing) {
                $this->existing = $existing;

                $singular = Sanitize::underscore( $singular );
                $plural  = Sanitize::underscore( $plural );

                $this->id = $this->existing->name;
                $this->resource = Registry::getTaxonomyResource($this->id) ?? [$singular, $plural, null, null];
                $this->postTypes = $this->existing->object_type;
                $this->args = array_merge($this->args, (array) $this->existing, $settings);

                return $this;
            }
        }

        $this->applyQuickLabels($singular, $plural);

        if (array_key_exists( 'hierarchical', $settings ) && $settings['hierarchical'] === true) :
            $settings['hierarchical'] = true;
        else :
            $settings['hierarchical'] = false;
        endif;

        // setup object for later use
        $plural       = Sanitize::underscore( $plural );
        $singular     = Sanitize::underscore( $singular );
        $this->resource = [$singular, $plural, $this->modelClass, $this->controllerClass];
        $this->id     = ! $this->id ? $singular : $this->id;

        if (array_key_exists( 'capabilities', $settings ) && $settings['capabilities'] === true) :
            $settings['capabilities'] = [
                'manage_terms' => 'manage_' . $plural,
                'edit_terms'   => 'manage_' . $plural,
                'delete_terms' => 'manage_' . $plural,
                'assign_terms' => 'edit_posts',
            ];
        endif;

        $defaults = [
            'show_admin_column' => false,
            'rewrite'           => ['slug' => Sanitize::dash( $this->id )],
        ];

        $this->args = array_merge( $this->args, $defaults, $settings );

        return $this;
    }

    /**
     * Apply Quick Labels
     *
     * @param string $singular
     * @param string $plural
     * @param bool $keep_case
     * @return Taxonomy $this
     */
    public function applyQuickLabels($singular, $plural = null, $keep_case = false)
    {
        if(!$plural) { $plural = Inflect::pluralize($singular); }

        // make lowercase
        $upperPlural   = $keep_case ? $plural : ucwords( $plural );
        $upperSingular = $keep_case ? $singular : ucwords( $singular );
        $lowerPlural   = $keep_case ? $plural : strtolower( $plural );

        $labels = [
            'add_new_item'               => __( 'Add New ' . $upperSingular, 'typerocket-profile'),
            'add_or_remove_items'        => __( 'Add or remove ' . $lowerPlural, 'typerocket-profile'),
            'all_items'                  => __( 'All ' . $upperPlural, 'typerocket-profile' ),
            'choose_from_most_used'      => __( 'Choose from the most used ' . $lowerPlural, 'typerocket-profile' ),
            'edit_item'                  => __( 'Edit ' . $upperSingular, 'typerocket-profile' ),
            'name'                       => __( $upperPlural, 'typerocket-profile' ),
            'menu_name'                  => __( $upperPlural, 'typerocket-profile' ),
            'new_item_name'              => __( 'New ' . $upperSingular . ' Name', 'typerocket-profile' ),
            'not_found'                  => __( 'No ' . $lowerPlural . ' found.', 'typerocket-profile' ),
            'parent_item'                => __( 'Parent ' . $upperSingular, 'typerocket-profile' ),
            'parent_item_colon'          => __( 'Parent ' . $upperSingular . ':', 'typerocket-profile' ),
            'popular_items'              => __( 'Popular ' . $upperPlural, 'typerocket-profile' ),
            'search_items'               => __( 'Search ' . $upperPlural, 'typerocket-profile' ),
            'separate_items_with_commas' => __( 'Separate ' . $lowerPlural . ' with commas', 'typerocket-profile' ),
            'singular_name'              => __( $upperSingular, 'typerocket-profile' ),
            'update_item'                => __( 'Update ' . $upperSingular, 'typerocket-profile' ),
            'view_item'                  => __( 'View ' . $upperSingular, 'typerocket-profile' )
        ];

        $this->args['label'] = $upperPlural;
        $this->args['labels'] = $labels;

        return $this;
    }

    /**
     * Get Existing Post Type
     *
     * @return \WP_Taxonomy|null
     */
    public function getExisting()
    {
        return $this->existing;
    }

    /**
     * Set the url slug used for rewrite rules
     *
     * @param string $slug
     *
     * @return Taxonomy $this
     */
    public function setSlug( $slug )
    {
        $this->args['rewrite'] = ['slug' => Sanitize::dash( $slug )];

        return $this;
    }
    
    /**
     * Set the resource
     *
     * @param array $resource
     *
     * @return Taxonomy $this
     */
    public function setResource( array $resource )
    {
        $this->resource = $resource;

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
     * Set the form main hook
     *
     * From hook to be added just above the title field
     *
     * @param bool|true|callable $value
     *
     * @return Taxonomy $this
     */
    public function setMainForm( $value = true )
    {
        if (is_callable( $value )) {
            $this->form['main'] = $value;
        } else {
            $this->form['main'] = true;
        }

        return $this;
    }

    /**
     * Set Hierarchical
     *
     * @param bool $bool
     *
     * @return Taxonomy $this
     */
    public function setHierarchical( $bool = true )
    {
        $this->args['hierarchical'] = (bool) $bool;

        return $this;
    }

    /**
     * Get the slug
     *
     * @return mixed
     */
    public function getSlug()
    {
        return $this->args['rewrite']['slug'];
    }

    /**
     * Register the taxonomy with WordPress
     *
     * @return Taxonomy $this
     */
    public function register()
    {
        if(!$this->existing) {
            $this->dieIfReserved();
        }

        do_action( 'tr_register_taxonomy_' . $this->id, $this );
        register_taxonomy( $this->id, $this->postTypes, $this->args );
        Registry::addTaxonomyResource($this->id, $this->resource);
        $this->attachHooks();

        return $this;
    }

    /**
     * Apply post types
     *
     * @param string|PostType $s
     *
     * @return Taxonomy $this
     */
    public function addPostType( $s )
    {

        if ($s instanceof PostType) {
            $s = $s->getId();
        } elseif (is_array( $s )) {
            foreach ($s as $n) {
                $this->addPostType( $n );
            }
        }

        if ( ! in_array( $s, $this->postTypes )) {
            $this->postTypes[] = $s;
        }

        return $this;

    }

    /**
     * Attach Hooks
     */
    public function attachHooks()
    {
        if(!$this->hooksAttached) {
            Registry::taxonomyHooks($this);
            $this->hooksAttached = true;
        }
    }

}
