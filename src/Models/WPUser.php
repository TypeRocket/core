<?php
namespace TypeRocket\Models;

class WPUser extends Model
{
    public $idColumn = 'ID';
    public $resource = 'users';

    public $builtin = [
        'user_login',
        'user_nicename',
        'user_email',
        'user_url',
        'user_activation_key',
        'user_status',
        'display_name',
        'user_registered',
        'id',
        'user_pass'
    ];

    public $guard = [
        'id'
    ];

    /**
     * Find post by ID
     *
     * @param $id
     *
     * @return $this
     */
    public function findById( $id )
    {
        $user = get_user_by( 'id', $id );
        $this->properties = (array) $user->data;

        return $this;
    }

    /**
     * Create users from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    function create( $fields )
    {
        $fields = $this->provisionFields( $fields );
        $builtin = $this->getFilteredBuiltinFields( $fields );

        if ( ! empty( $builtin )) {
            remove_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );
            $user  = wp_insert_user( $builtin );
            add_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );

            if ($user instanceof \WP_Error || ! is_int( $user )) {
                $this->errors = isset( $user->errors ) ? $user->errors : [];
            } else {
                $this->findById($user);
            }
        }

        $this->saveMeta( $fields );

        return $this;
    }

    /**
     * Update user from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    function update( $fields )
    {
        $id = $this->getID();
        if ($id != null) {
            $fields = $this->provisionFields( $fields );

            $builtin = $this->getFilteredBuiltinFields( $fields );
            if ( ! empty( $builtin )) {
                remove_action( 'profile_update', 'TypeRocket\Http\Responders\Hook::users' );
                $builtin['ID'] = $id;
                wp_update_user( $builtin );
                add_action( 'profile_update', 'TypeRocket\Http\Responders\Hook::users' );
                $this->findById($id);
            }

            $this->saveMeta( $fields );
        } else {
            $this->errors = ['No item to update'];
        }

        return $this;
    }

    /**
     * Save user meta fields from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    private function saveMeta( $fields )
    {
        $fields = $this->getFilteredMetaFields( $fields );
        $id = $this->getID();
        if ( ! empty( $fields ) && ! empty( $id )) :
            foreach ($fields as $key => $value) :
                if (is_string( $value )) {
                    $value = trim( $value );
                }

                $current_value = get_user_meta( $id, $key, true );

                if (isset( $value ) && $value !== $current_value) :
                    update_user_meta( $id, $key, $value );
                elseif ( ! isset( $value ) || $value === "" && ( isset( $current_value ) || $current_value === "" )) :
                    delete_user_meta( $id, $key );
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
    public function getBaseFieldValue( $field_name )
    {
        $id = $this->getID();
        if (in_array( $field_name, $this->builtin )) {

            switch ($field_name) {
                case 'id' :
                    $data = $id;
                    break;
                case 'user_pass' :
                    $data = '';
                    break;
                default :
                    $data = $this->properties[$field_name];
                    break;
            }
        } else {
            $data = get_metadata( 'user', $id, $field_name, true );
        }

        return $this->getValueOrNull( $data );
    }
}