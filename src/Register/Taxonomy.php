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

    /**
     * Make Taxonomy. Do not use before init.
     *
     * @param string $singular singular name is required
     * @param string $plural plural name
     * @param array $settings args override and extend
     */
    public function __construct( $singular, $plural = null, $settings = [])
    {

        if (is_null( $plural )) {
            $plural = Inflect::pluralize( $singular );
        }

        $upperPlural   = ucwords( $plural );
        $upperSingular = ucwords( $singular );
        $lowerPlural   = strtolower( $plural );

        $labels = [
            'add_new_item'               => __( 'Add New ' . $upperSingular ),
            'add_or_remove_items'        => __( 'Add or remove ' . $lowerPlural ),
            'all_items'                  => __( 'All ' . $upperPlural ),
            'choose_from_most_used'      => __( 'Choose from the most used ' . $lowerPlural ),
            'edit_item'                  => __( 'Edit ' . $upperSingular ),
            'name'                       => __( $upperPlural ),
            'menu_name'                  => __( $upperPlural ),
            'new_item_name'              => __( 'New ' . $upperSingular . ' Name' ),
            'not_found'                  => __( 'No ' . $lowerPlural . ' found.' ),
            'parent_item'                => __( 'Parent ' . $upperSingular ),
            'parent_item_colon'          => __( 'Parent ' . $upperSingular . ':' ),
            'popular_items'              => __( 'Popular ' . $upperPlural ),
            'search_items'               => __( 'Search ' . $upperPlural ),
            'separate_items_with_commas' => __( 'Separate ' . $lowerPlural . ' with commas' ),
            'singular_name'              => __( $upperSingular ),
            'update_item'                => __( 'Update ' . $upperSingular ),
            'view_item'                  => __( 'View ' . $upperSingular )
        ];

        if (array_key_exists( 'hierarchical', $settings ) && $settings['hierarchical'] === true) :
            $settings['hierarchical'] = true;
        else :
            $settings['hierarchical'] = false;
        endif;

        // setup object for later use
        $plural       = Sanitize::underscore( $plural );
        $singular     = Sanitize::underscore( $singular );
        $this->resource = [$singular, $plural];
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
            'labels'            => $labels,
            'show_admin_column' => false,
            'rewrite'           => ['slug' => Sanitize::dash( $this->id )],
        ];

        $this->args = array_merge( $defaults, $settings );

        return $this;
    }

    /**
     * Set the url slug used for rewrite rules
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
     * Set the resource
     *
     * @param $resource
     *
     * @return $this
     */
    public function setResource( array $resource )
    {
        $this->resource = $resource;

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
     * Set the form main hook
     *
     * From hook to be added just above the title field
     *
     * @param bool|true|callable $value
     *
     * @return $this
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
     * @return $this
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
     * @return $this
     */
	public function register()
	{
		if( ! $this->isReserved() ) {
			do_action( 'tr_register_taxonomy_' . $this->id, $this );
			register_taxonomy( $this->id, $this->postTypes, $this->args );
		}
		Registry::addTaxonomyResource($this->id, $this->resource);

		return $this;
	}

    /**
     * Apply post types
     *
     * @param string|PostType $s
     *
     * @return $this
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

}
