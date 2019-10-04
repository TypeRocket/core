<?php
namespace TypeRocket\Models;

use ArrayObject;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Exceptions\ModelNotFoundException;
use TypeRocket\Http\Fields;
use TypeRocket\Models\Meta\WPUserMeta;
use TypeRocket\Models\Traits\MetaData;
use WP_Error;

class WPUser extends Model
{
    use MetaData;

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
     * Get Meta Model Class
     *
     * @return string
     */
    protected function getMetaModelClass()
    {
        return WPUserMeta::class;
    }

    /**
     * Get ID Columns
     *
     * @return array
     */
    protected function getMetaIdColumns()
    {
        return [
            'local' => 'ID',
            'foreign' => 'user_id',
        ];
    }

    /**
     * Users Posts
     *
     * @param string $modelClass
     *
     * @return null|Model
     */
    public function posts( $modelClass = WPPost::class )
    {
        /** @var WPPost $post */
        $post = new $modelClass;
        $post_type = $post->getPostType();
        return $this->hasMany( $modelClass, 'post_author' )->where('post_type', $post_type);
    }

    /**
     * Find post by ID
     *
     * @param string $id
     *
     * @return $this
     * @throws ModelNotFoundException
     */
    public function getUser( $id )
    {
        $user = get_user_by( 'id', $id );

        if(!$user) {
            $class = static::class;
            throw new ModelNotFoundException("ID $id of {$class} class not found");
        }

        $this->fetchResult(  (array) $user->data );

        return $this;
    }

    /**
     * Create users from TypeRocket fields
     *
     * @param array|Fields $fields
     *
     * @return $this
     * @throws ModelException
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

            if ($user instanceof WP_Error || ! is_int( $user )) {
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
     * @param array|Fields $fields
     *
     * @return $this
     * @throws ModelException
     * @throws ModelNotFoundException
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
     * @param array|ArrayObject $fields
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
     * @param string $field_name
     *
     * @return null
     */
    public function getBaseFieldValue( $field_name )
    {
        $id = $this->getID();

        $field_name = $field_name === 'ID' ? 'id' : $field_name;

        if($field_name == 'user_pass') {
            $data = '';
        } else {
            $data = $this->getProperty($field_name);
        }

        if(is_null($data)) {
            $data = get_metadata( 'user', $id, $field_name, true );
        }

        return $this->getValueOrNull( $data );
    }

    /**
     * Builtin
     *
     * @param array $builtin
     * @return mixed
     */
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