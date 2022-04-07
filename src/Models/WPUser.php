<?php
namespace TypeRocket\Models;

use ArrayObject;
use TypeRocket\Core\Container;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Http\Fields;
use TypeRocket\Models\Meta\WPUserMeta;
use TypeRocket\Models\Traits\MetaData;
use WP_Error;

class WPUser extends Model implements AuthUser
{
    use MetaData;

    protected $idColumn = 'ID';
    protected $resource = 'users';
    protected $routeResource = 'user';
    /** @var \WP_User */
    protected $wpUser = null;
    protected $fieldOptions = [
        'key' => 'user_email',
        'value' => 'ID',
    ];

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

    protected $private = [
        'user_pass'
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
        return $wpdb->users;
    }

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
     * @return string|null
     */
    public function getRestMetaType()
    {
        return 'user';
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
        return $this->hasMany( $modelClass, 'post_author');
    }

    /**
     * Find WP_User Instance
     *
     * @param \WP_User|int|null $user
     * @param bool $returnModel
     *
     * @return \WP_User|$this|null
     */
    public function wpUser( $user = null, $returnModel = false )
    {
        if( !$user && $this->wpUser instanceof \WP_User ) {
            return $this->wpUser;
        }

        if( !$user && $this->getID() ) {
            return $this->wpUser($this->getID());
        }

        if( !$user ) {
            return $this->wpUser;
        }

        if( is_numeric($user) ) {
            $user = get_user_by( 'id', $user );
        }

        if( $user instanceof \WP_User) {
            $this->wpUser = $user;
            $this->castProperties( $user->to_array() );
        }

        return $returnModel ? $this : $this->wpUser;
    }

    /**
     * After Properties Are Cast
     *
     * Create an Instance of WP_Post
     *
     * @return Model
     */
    protected function afterCastProperties()
    {
        if(!$this->wpUser && $this->getCache()) {
            $_user = (object) $this->properties;
            $this->wpUser = new \WP_User($_user);
            update_user_caches( $this->wpUser );
        }

        return parent::afterCastProperties();
    }

    /**
     * Is Capable
     *
     * @param $capability
     * @return bool
     */
    public function isCapable($capability)
    {
        /** @var \WP_User|null $user */
        $user = $this->wpUser();

        if(!$user || !$user->has_cap($capability)) {
            return false;
        }

        return true;
    }

    /**
     * Has Role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        /** @var \WP_User|null $user */
        $user = $this->wpUser();

        return in_array($role, $user->roles) ? true :  false;
    }

    /**
     * Change Users Role
     *
     * WordPress is not designed to give users multiple roles.
     *
     * @param $role
     * @param array $remove a blank array removes all old roles
     * @return $this
     * @throws \Exception
     */
    public function changeRoleTo($role, $remove = [])
    {
        /** @var \WP_User|null $user */
        $user = $this->wpUser();

        if(!wp_roles()->get_role($role)) {
            throw new \Exception("Role {$role} does not exist. User's role can not be changed");
        }

        foreach ($user->roles as $old) {
            if(empty($remove) || in_array($old, $remove)) {
                $user->remove_role( $old );
            }
        }

        $user->add_role( $role );

        return $this;
    }

    /**
     * Is Current User
     *
     * @return bool
     */
    public function isCurrent()
    {
        return $this->getID() == Container::resolveAlias(AuthUser::ALIAS)->getID();
    }

    /**
     * Create users from TypeRocket fields
     *
     * @param array|Fields $fields
     *
     * @return $this
     * @throws ModelException
     */
    public function create( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );
        $builtin = $this->getFilteredBuiltinFields( $fields );
        $user = null;

        do_action('typerocket_model_create', $this, $fields);

        if ( ! empty( $builtin )) {
            $builtin = $this->slashBuiltinFields($builtin);
            remove_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );
            $user  = wp_insert_user( $builtin );
            add_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );

            if ($user instanceof WP_Error || ! is_int( $user )) {
                throw new ModelException('WPUser not created');
            } else {
                $user = $this->findById($user);
            }
        }

        $this->saveMeta( $fields );

        do_action('typerocket_model_after_create', $this, $fields, $user);

        return $user;
    }

    /**
     * Update user from TypeRocket fields
     *
     * @param array|Fields $fields
     *
     * @return $this
     * @throws ModelException
     */
    public function update( $fields = [] )
    {
        $id = $this->getID();
        if ($id != null) {
            $fields = $this->provisionFields( $fields );
            $builtin = $this->getFilteredBuiltinFields( $fields );
            $user = null;

            do_action('typerocket_model_update', $this, $fields);

            if ( ! empty( $builtin )) {
                $builtin = $this->slashBuiltinFields($builtin);
                remove_action( 'profile_update', 'TypeRocket\Http\Responders\Hook::users' );
                $builtin['ID'] = $id;
                $user_login = $builtin['user_login'] ?? null;
                $user_login_current = $this->getFieldValue('user_login');

                if( !empty($user_login) && $user_login !== $user_login_current ) {
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

            do_action('typerocket_model_after_update', $this, $fields, $user);

        } else {
            $this->errors = ['No item to update'];
        }

        return $this;
    }

    /**
     * Delete & Reassign User
     *
     * This will delete the user and resign their content to another user
     *
     * @param int $to_user_id
     * @param null|int $user_id
     *
     * @return $this
     * @throws ModelException
     */
    public function deleteAndReassign($to_user_id, $user_id = null)
    {
        if(is_null($user_id) && $this->hasProperties()) {
            $user_id = $this->getID();
        }

        do_action('typerocket_model_delete', $this, $user_id);

        $delete = wp_delete_user($user_id, $to_user_id);

        if ( !$delete ) {
            throw new ModelException('WPUser not deleted');
        }

        do_action('typerocket_model_after_delete', $this, $to_user_id, $delete);

        return $this;
    }

    /**
     * Delete User
     *
     * This will delete the user and all of their posts
     *
     * @param null|int $ids
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

        $delete = wp_delete_user($ids);

        if ( !$delete ) {
            throw new ModelException('WPUser not deleted');
        }

        do_action('typerocket_model_after_delete', $this, $ids, $delete);

        return $this;
    }

    /**
     * Save user meta fields from TypeRocket fields
     *
     * @param array|ArrayObject $fields
     *
     * @return $this
     */
    public function saveMeta( $fields )
    {
        $fields = $this->getFilteredMetaFields( $fields );
        $id = $this->getID();
        if ( ! empty( $fields ) && ! empty( $id )) :
            foreach ($fields as $key => $value) :
                if (is_string( $value )) {
                    $value = trim( $value );
                }

                $current_value = get_user_meta( $id, $key, true );
                $value = $this->getNewArrayReplaceRecursiveValue($key, $current_value, $value);

                if (isset( $value ) && $value !== $current_value) :
                    $value = wp_slash($value);
                    update_user_meta( $id, $key, $value );
                    do_action('typerocket_after_save_meta_user', $id, $key, $value, $current_value, $this);
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