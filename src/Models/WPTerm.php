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
    public const TAXONOMY = null;
    protected $taxonomy = null;
    /** @var \WP_Term */
    protected $wpTerm = null;
    protected $fieldOptions = [
        'key' => 'name',
        'value' => 'term_id',
    ];

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
        $this->setTaxonomy($taxonomy ?? static::TAXONOMY);
        parent::__construct();
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
     * Set Taxonomy
     *
     * @param string|null $taxonomy
     * @param bool $init init query when setting taxonomy
     * @return $this
     */
    public function setTaxonomy($taxonomy = null, $init = false)
    {
        if($taxonomy) { $this->taxonomy = $taxonomy; }
        if($init) { $this->initQuery($this->query); }

        return $this;
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
        return $wpdb->terms;
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
        if($tax = $this->getTaxonomy()) {
            /** @var \wpdb $wpdb */
            global $wpdb;
            $tt = $wpdb->term_taxonomy;
            $query->setSelectTable(null);
            $query->select($this->table.'.*', $tt.'.taxonomy', $tt.'.term_taxonomy_id', $tt.'.description');
            $query->join($tt, $tt.'.term_id', $this->table.'.term_id');
            $query->where($tt.'.taxonomy', $tax);
        }

        return $query;
    }

    /**
     * Get JsonApiResource
     *
     * @return string|null
     */
    public function getRouteResource()
    {
        return $this->getTaxonomy();
    }

    /**
     * @return string|null
     */
    public function getRestMetaType()
    {
        return 'term';
    }

    /**
     * @return string|null
     */
    public function getRestMetaSubtype()
    {
        return $this->getTaxonomy();
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
     * Get Term Permalink
     *
     * @return string|\WP_Error
     */
    public function permalink()
    {
        return get_term_link($this->wpTerm(), $this->getTaxonomy());
    }

    /**
     * @return string|\WP_Error
     */
    public function getSearchUrl()
    {
        return $this->permalink();
    }

    /**
     * Belongs To Post
     *
     * @param string $modelClass
     * @param null|callable $scope
     * @param bool $reselect
     *
     * @return Model|null
     */
    public function belongsToPost($modelClass, $scope = null, $reselect = true)
    {
        global $wpdb;
        return $this->belongsToMany($modelClass, $wpdb->term_relationships, 'term_taxonomy_id', 'object_id', $scope, $reselect);
    }

    /**
     * After Properties Are Cast
     *
     * Create an Instance of WP_Term
     *
     * @return Model
     */
    protected function afterCastProperties()
    {
        if(!$this->wpTerm && $this->getTaxonomy() && $this->getCache()) {
            $_term = sanitize_term( (object) $this->properties, $this->getTaxonomy(), 'raw' );
            wp_cache_add( $_term->term_id, $_term, 'terms' );
            $this->wpTerm = new \WP_Term($_term);
        }

        return parent::afterCastProperties();
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
     * Get WP_Term Instance
     *
     * @param \WP_Term|null|int
     * @param bool $returnModel
     *
     * @return \WP_Term|$this|null
     */
    public function wpTerm( $term = null, $returnModel = false)
    {
        if( !$term && $this->wpTerm instanceof \WP_Term ) {
            return $this->wpTerm;
        }

        if( !$term && $this->getID() ) {
            return $this->wpTerm($this->getID());
        }

        if(!$term) {
            return $this->wpTerm;
        }

        $term = get_term( $term );

        if( $term instanceof \WP_Term) {
            $this->setTaxonomy($term->taxonomy, true);
            $this->wpTerm = $term;
            $this->castProperties( $term->to_array() );
        }

        return $returnModel ? $this : $this->wpTerm;
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
     */
    public function create( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );
        $builtin = $this->getFilteredBuiltinFields($fields);
        $term = null;

        do_action('typerocket_model_create', $this, $fields);

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

        do_action('typerocket_model_after_create', $this, $fields, $term);

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
            $term = null;

            do_action('typerocket_model_update', $this, $fields);

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

            do_action('typerocket_model_after_update', $this, $fields, $term);

        } else {
            $this->errors = ['No item to update'];
        }

        return $this;
    }

    /**
     * Update Term Count
     *
     * @throws ModelException
     */
    public function updateTermCount()
    {
        $id = $this->getID();

        if(!$this->getTaxonomy() || !$id) {
            throw new ModelException('Taxonomy and ID required to use updateTermCount()');
        }

        wp_update_term_count_now([$id], $this->getTaxonomy());

        return $this;
    }

    /**
     * Delete Term
     *
     * @param null|int|\WP_Term $ids
     *
     * @return $this
     * @throws ModelException
     */
    public function delete($ids = null)
    {
        if(is_null($ids) && $this->hasProperties()) {
            $ids = $this->getID();
        }

        if(is_array($ids)) {
            throw new ModelException(static::class . ' not deleted: bulk deleting not supported due to WordPress performance issues.');
        }

        do_action('typerocket_model_delete', $this, $ids);

        $delete = wp_delete_term($ids, $this->wpTerm($ids)->taxonomy);

        if ( $delete instanceof \WP_Error ) {
            throw new ModelException('WPTerm not deleted: ' . $delete->get_error_message());
        }

        if ( !$delete ) {
            throw new ModelException('WPTerm not deleted');
        }

        do_action('typerocket_model_after_delete', $this, $ids, $delete);

        return $this;
    }

    /**
     * Save term meta fields from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    public function saveMeta( $fields )
    {
        $fields = $this->getFilteredMetaFields($fields);
        $id = $this->getID();
        if ( ! empty($fields) && ! empty( $id ) ) :
            foreach ($fields as $key => $value) :
                if (is_string( $value )) {
                    $value = trim( $value );
                }

                $current_value = get_term_meta( $id, $key, true );
                $value = $this->getNewArrayReplaceRecursiveValue($key, $current_value, $value);

                if (( isset( $value ) && $value !== "" ) && $value !== $current_value) :
                    $value = wp_slash($value);
                    update_term_meta( $id, $key, $value );
                    do_action('typerocket_after_save_meta_term', $id, $key, $value, $current_value, $this);
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
    public function slashBuiltinFields( $builtin )
    {
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
