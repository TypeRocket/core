<?php
namespace TypeRocket\Models;

use TypeRocket\Database\Query;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Models\Meta\WPTermMeta;
use TypeRocket\Models\Traits\MetaData;

class WPTerm extends Model
{
    use MetaData;

    protected $idColumn = 'term_id';
    protected $resource = 'terms';
    protected $taxonomy = null;

    protected $builtin = [
        'description',
        'name',
        'slug',
        'term_group'
    ];

    protected $guard = [
        'term_id',
        'term_taxonomy_id',
        'taxonomy',
        'term_group',
        'parent',
        'count',
    ];

    public function __construct($taxonomy = null)
    {
        if($taxonomy) { $this->taxonomy = $taxonomy; }
        parent::__construct();
    }

    /**
     * Get Meta Model Class
     *
     * @return string
     */
    protected function getMetaModelClass()
    {
        return WPTermMeta::class;
    }

    /**
     * Get ID Columns
     *
     * @return array
     */
    protected function getMetaIdColumns()
    {
        return [
            'local' => 'term_id',
            'foreign' => 'term_id',
        ];
    }

    /**
     * Set Taxonomy
     *
     * @param string $taxonomy
     * @param bool $init init query when setting taxonomy
     * @return $this
     */
    public function setTaxonomy($taxonomy, $init = true)
    {
        $this->taxonomy = $taxonomy;
        if($init) { $this->initQuery($this->query); }

        return $this;
    }

    /**
     * Init Query
     *
     * @param Query $query
     *
     * @return mixed
     */
    protected function initQuery(Query $query)
    {
        if($this->taxonomy) {
            /** @var \wpdb $wpdb */
            global $wpdb;
            $tt = $wpdb->prefix . 'term_taxonomy';
            $query->setSelectTable(null);
            $query->select($this->table.'.*', $tt.'.taxonomy', $tt.'.term_taxonomy_id', $tt.'.description');
            $query->join($tt, $tt.'.term_id', $this->table.'.term_id');
            $query->where($tt.'.taxonomy', $this->taxonomy);
        }

        return $query;
    }

    /**
     * Get Term Taxonomy
     *
     * @return null|\TypeRocket\Models\Model
     */
    public function termTaxonomies()
    {
        return $this->hasMany( WPTermTaxonomy::class, 'term_id' );
    }

    /**
     * Get Taxonomy
     *
     * @return string
     */
    public function getTaxonomy()
    {
        return $this->taxonomy;
    }

    /**
     * Return table name in constructor
     *
     * @param \wpdb $wpdb
     *
     * @return string
     */
    public function initTable( $wpdb )
    {
        return $wpdb->prefix . 'terms';
    }

    /**
     * Get comment by ID
     *
     * @param string $id
     *
     * @return $this
     */
    public function getTerm( $id )
    {
        $this->fetchResult(  get_term( $id, $this->taxonomy, ARRAY_A ) );
        return $this;
    }

    /**
     * Create term from TypeRocket fields
     *
     * Set the taxonomy property on extended model so they
     * are saved to the correct type. See the CategoriesModel
     * as example.
     *
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return WPTerm
     * @throws \TypeRocket\Exceptions\ModelException
     * @throws \ReflectionException
     */
    public function create( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );
        $builtin = $this->getFilteredBuiltinFields($fields);

        if ( ! empty( $builtin ) ) {
            $builtin = $this->slashBuiltinFields($builtin);
            remove_action('create_term', 'TypeRocket\Http\Responders\Hook::taxonomies');

            $name = $builtin['name'];
            unset($builtin['name']);
            $term = wp_insert_term( $name, $this->taxonomy, $builtin );
            add_action('create_term', 'TypeRocket\Http\Responders\Hook::taxonomies');

            if ( $term instanceof \WP_Error || empty($term['term_id']) ) {
                throw new ModelException('WPTerm not created: name field and taxonomy property are required');
            } else {
                $term = (new self($this->taxonomy))->findById( $term['term_id'] );
            }
        }

        $this->saveMeta( $fields );

        return $term;
    }

    /**
     * Update term from TypeRocket fields
     *
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return $this
     * @throws \TypeRocket\Exceptions\ModelException
     */
    public function update( $fields = [] )
    {
        $id = $this->getID();
        if($id != null) {
            $fields = $this->provisionFields( $fields );
            $builtin = $this->getFilteredBuiltinFields($fields);

            if ( ! empty( $builtin ) ) {
                $builtin = $this->slashBuiltinFields($builtin);
                remove_action('edit_term', 'TypeRocket\Http\Responders\Hook::taxonomies');
                $term = wp_update_term( $id, $this->taxonomy, $builtin );
                add_action('edit_term', 'TypeRocket\Http\Responders\Hook::taxonomies');

                if ( $term instanceof \WP_Error || empty($term['term_id']) ) {
                    throw new ModelException('WPTerm not updated: name is required');
                } else {
                    $this->findById($id);
                }
            }

            $this->saveMeta( $fields );

        } else {
            $this->errors = ['No item to update'];
        }

        return $this;
    }

    /**
     * Save term meta fields from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    private function saveMeta( $fields )
    {
        $fields = $this->getFilteredMetaFields($fields);
        $id = $this->getID();
        if ( ! empty($fields) && ! empty( $id ) ) :
            foreach ($fields as $key => $value) :
                if (is_string( $value )) {
                    $value = trim( $value );
                }

                $current_value = get_term_meta( $id, $key, true );

                if (( isset( $value ) && $value !== "" ) && $value !== $current_value) :
                    update_term_meta( $id, $key, wp_slash($value) );
                elseif ( ! isset( $value ) || $value === "" && ( isset( $current_value ) || $current_value === "" )) :
                    delete_term_meta( $id, $key );
                endif;

            endforeach;
        endif;

        return $this;
    }

    /**
     * Get base field value
     *
     * Some fields need to be saved as serialized arrays. Getting
     * the field by the base value is used by Fields to populate
     * their values.
     *
     * @param string $field_name
     *
     * @return null
     */
    public function getBaseFieldValue( $field_name )
    {
        $id = $this->getID();
        $data = $this->getProperty($field_name);

        if(is_null($data)) {
            $data = get_metadata( 'term', $id, $field_name, true );
        }

        return $this->getValueOrNull($data);
    }

    /**
     * @param array $builtin
     * @return mixed
     */
    public function slashBuiltinFields( $builtin ) {

        $fields = [
            'name',
            'description'
        ];

        foreach ($fields as $field) {
            if(!empty($builtin[$field])) {
                $builtin[$field] = wp_slash( $builtin[$field] );
            }
        }

        return $builtin;
    }

}
