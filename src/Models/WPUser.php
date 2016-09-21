<?php
namespace TypeRocket\Models;

use TypeRocket\Exceptions\ModelException;
use TypeRocket\Models\Meta\WPUserMeta;

class WPUser extends Model
{
    protected $idColumn = 'ID';
    protected $resource = 'users';

    protected $builtin = [
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

    protected $guard = [
        'id'
    ];

    /**
     * Get User Meta
     *
     * @return null|\TypeRocket\Models\Model
     */
    public function meta()
    {
        return $this->hasMany( WPUserMeta::class, 'user_id' );
    }

    /**
     * Users Posts
     *
     * @param $modelClass
     *
     * @return null|\TypeRocket\Models\Model
     */
    public function posts( $modelClass )
    {
        /** @var WPPost $post */
        $post = new $modelClass;

        return $this->hasMany( $modelClass, 'post_author' )->where('post_type', $post->getPostType());
    }

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
        $this->fetchResult(  (array) $user->data );

        return $this;
    }

    /**
     * Create users from TypeRocket fields
     *
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return $this
     * @throws \TypeRocket\Exceptions\ModelException
     */
    function create( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );
        $builtin = $this->getFilteredBuiltinFields( $fields );

        if ( ! empty( $builtin )) {
            $builtin = $this->slashBuiltinFields($builtin);
            remove_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );
            $user  = wp_insert_user( $builtin );
            add_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );

            if ($user instanceof \WP_Error || ! is_int( $user )) {
                throw new ModelException('WPUser not created');
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
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return $this
     * @throws \TypeRocket\Exceptions\ModelException
     */
    function update( $fields = [] )
    {
        $id = $this->getID();
        if ($id != null) {
            $fields = $this->provisionFields( $fields );

            $builtin = $this->getFilteredBuiltinFields( $fields );
            if ( ! empty( $builtin )) {
                $builtin = $this->slashBuiltinFields($builtin);
                remove_action( 'profile_update', 'TypeRocket\Http\Responders\Hook::users' );
                $builtin['ID'] = $id;

                if( !empty($builtin['user_login']) ) {
                    throw new ModelException('WPUser not updated: You can not change the user_login');
                }

                $user = wp_update_user( $builtin );
                add_action( 'profile_update', 'TypeRocket\Http\Responders\Hook::users' );

                if( empty($user) ) {
                    throw new ModelException('WPUser not updated');
                }
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
                    update_user_meta( $id, $key, wp_slash($value) );
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

    public function slashBuiltinFields( $builtin ) {

        $fields = [
            'display_name',
        ];

        foreach ($fields as $field) {
            if(!empty($builtin[$field])) {
                $builtin[$field] = wp_slash( $builtin[$field] );
            }
        }

        return $builtin;
    }
}