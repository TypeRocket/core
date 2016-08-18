<?php
namespace TypeRocket\Models;

abstract class TermsModel extends Model
{
    public $idColumn = 'term_id';
    public $resource = 'terms';

    protected $taxonomy = null;

    protected $builtin = [
        'description',
        'name',
        'slug',
        'parent'
    ];

    protected $guard = [
        'term_id',
        'term_taxonomy_id',
        'taxonomy',
        'term_group',
        'parent',
        'count',
    ];

    /**
     * Get comment by ID
     *
     * @param $id
     *
     * @return $this
     */
    public function findById( $id )
    {
        $this->properties = get_term( $id, $this->taxonomy, ARRAY_A );
        return $this;
    }

    /**
     * Create term from TypeRocket fields
     *
     * Set the taxonomy property on extended model so they
     * are saved to the correct type. See the CategoriesModel
     * as example.
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    public function create( $fields )
    {
        $fields = $this->secureFields($fields);
        $fields = array_merge($this->default, $fields, $this->static);
        $builtin = $this->getFilteredBuiltinFields($fields);

        if ( ! empty( $builtin ) ) {
            remove_action('create_term', 'TypeRocket\Http\Responders\Hook::taxonomies');

            $name = $builtin['name'];
            unset($builtin['name']);
            $term = wp_insert_term( $name, $this->taxonomy, $builtin );
            add_action('create_term', 'TypeRocket\Http\Responders\Hook::taxonomies');

            if ( $term instanceof \WP_Error || $term === 0 ) {
                $default      = 'name is required';
                $this->errors = ! empty( $term->errors ) ? $term->errors : [$default];
            } else {
                $this->findById($term);
            }
        }

        $this->saveMeta( $fields );

        return $this;
    }

    /**
     * Update term from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    public function update( $fields )
    {
        $id = $this->getID();
        if($id != null) {
            $fields = $this->secureFields($fields);
            $fields = array_merge($fields, $this->static);
            $builtin = $this->getFilteredBuiltinFields($fields);

            if ( ! empty( $builtin ) ) {
                remove_action('edit_term', 'TypeRocket\Http\Responders\Hook::taxonomies');
                $term = wp_update_term( $id, $this->taxonomy, $builtin );
                add_action('edit_term', 'TypeRocket\Http\Responders\Hook::taxonomies');

                if ( $term instanceof \WP_Error || $term === 0 ) {
                    $default      = 'name is required';
                    $this->errors = ! empty( $term->errors ) ? $term->errors : [$default];
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
        if ( ! empty($fields) && ! empty( $this->id ) ) :
            foreach ($fields as $key => $value) :
                if (is_string( $value )) {
                    $value = trim( $value );
                }

                $current_value = get_term_meta( $this->id, $key, true );

                if (( isset( $value ) && $value !== "" ) && $value !== $current_value) :
                    update_term_meta( $this->id, $key, $value );
                elseif ( ! isset( $value ) || $value === "" && ( isset( $current_value ) || $current_value === "" )) :
                    delete_term_meta( $this->id, $key );
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
     * @param $field_name
     *
     * @return null
     */
    protected function getBaseFieldValue( $field_name )
    {
        if(in_array($field_name, $this->builtin)) {
            switch ($field_name) {
                case 'term_id' :
                case 'name' :
                case 'description' :
                case 'slug' :
                    $data = $this->properties[$field_name];
                    break;
                default :
                    $data = get_term_meta( $field_name, $this->getID(), 'raw' );
                    break;
            }
        } else {
            $data = get_metadata( 'term', $this->getID(), $field_name, true );
        }

        return $this->getValueOrNull($data);
    }

}
