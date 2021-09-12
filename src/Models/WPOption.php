<?php
namespace TypeRocket\Models;

/**
 * Class WPOption
 *
 * This class is not to be used as a true model for create, delete, save,
 * and update. It is kept here for legacy functionality.
 *
 * @package TypeRocket\Models
 */
class WPOption extends Model
{
    protected $idColumn = 'option_id';
    protected $resource = 'options';
    protected $routeResource = 'option';
    protected $fieldOptions = [
        'key' => 'option_name',
        'value' => 'option_id',
    ];

    /**
     * Return table name in constructor
     *
     * @param \wpdb $wpdb
     *
     * @return string
     */
    public function initTable( $wpdb )
    {
        return $wpdb->options;
    }

    /**
     * Do nothing since options are not true resources
     *
     * @param string $id
     *
     * @return $this
     */
    public function findById( $id )
    {
        return $this;
    }

    /**
     * Create options from TypeRocket fields
     *
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return $this
     */
    public function create( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );
        $this->saveOptions( $fields );

        return $this;
    }

    /**
     * Update options from TypeRocket fields
     *
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return $this
     */
    public function update( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );
        $this->saveOptions( $fields );

        return $this;
    }

    /**
     * Save options' fields from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    public function saveOptions( $fields )
    {
        if ( ! empty( $fields )) {
            foreach ($fields as $key => $value) :

                if (is_string( $value )) {
                    $value = trim( $value );
                }

                $current_value = get_option( $key );
                $value = $this->getNewArrayReplaceRecursiveValue($key, $current_value, $value);

                if (( isset( $value ) && $value !== "" ) && $current_value !== $value) :
                    update_option( $key, $value );
                    do_action('typerocket_after_save_meta_option', null, $key, $value, $current_value, $this);
                elseif ( ! isset( $value ) || $value === "" && ( isset( $current_value ) || $current_value === "" )) :
                    delete_option( $key );
                endif;

            endforeach;
        }

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
        $data = get_option( $field_name, null );
        return $this->getValueOrNull($data);
    }

    /**
     * Get ID as TRUE
     *
     * Always get the ID as true since wp_options is more
     * of a central store
     *
     * @return int
     */
    public function getID()
    {
        return true;
    }
}