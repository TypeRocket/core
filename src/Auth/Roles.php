<?php
namespace TypeRocket\Auth;

use TypeRocket\Utility\Str;

class Roles
{
    protected $guardRoles = [
        'administrator',
        'editor',
        'author',
        'subscriber',
        'contributor',
        'super_admin',
    ];

    /**
     * @return array[]
     */
    public function all()
    {
        $objects = wp_roles()->role_objects;
        $names = wp_roles()->role_names;
        return array_map(function($role, $name) {
            /** @var $role \WP_Role */
            return [ 'name' => $role->name, 'capabilities' => $role->capabilities, 'label' => $name ];
        }, $objects, $names);
    }

    /**
     * @return array
     */
    public function capabilities()
    {
        $roles = wp_roles()->role_objects;
        $capabilities = [];

        foreach ($roles as $role) {
            foreach ($role->capabilities as $cap => $value) {
                $capabilities[$cap][] = $role->name;
            }
        }

        return $capabilities;
    }

    /**
     * @return array[]
     */
    public function getEditableRoles()
    {
        return get_editable_roles();
    }

    /**
     * @param string $role
     * @param array $capabilities
     * @param null|string $label
     *
     * @return $this
     */
    public function add($role, array $capabilities, $label = null)
    {
        wp_roles()->add_role($role, $label ?? Str::makeWords($role, true), $capabilities);

        return $this;
    }

    /**
     * @param string $role
     *
     * @return $this
     * @throws \Exception
     */
    public function remove($role)
    {
        if(in_array($role, $this->guardRoles)) {
            throw new \Exception('Can not delete guarded role: ' . $role);
        }

        wp_roles()->remove_role($role);

        return $this;
    }

    /**
     * @param $role
     *
     * @return bool
     */
    public function exists($role)
    {
        return wp_roles()->is_role($role);
    }

    /**
     * @param $role
     *
     * @return \WP_Role|null
     */
    public function get($role)
    {
        return wp_roles()->get_role($role);
    }

    /**
     * @param string $role
     * @param array $capabilities
     * @param bool $remove
     *
     * @return \WP_Role|null
     */
    public function updateRolesCapabilities($role, array $capabilities, $remove = false)
    {
        $role = wp_roles()->get_role($role);

        if($role instanceof \WP_Role) {
            foreach ($capabilities as $capability) {
                if($remove) {
                    $role->remove_cap($capability);
                } else {
                    $role->add_cap($capability);
                }
            }
        }

        return $role;
    }

    /**
     * Get Post Type Capabilities
     *
     * @param string $singular
     * @param string $plural
     *
     * @return array
     */
    public function getCustomPostTypeCapabilities($singular = 'post', $plural = 'posts')
    {
        return [
            'edit_post'      => "edit_$singular",
            'read_post'      => "read_$singular",
            'delete_post'        => "delete_$singular",
            'edit_posts'         => "edit_$plural",
            'edit_others_posts'  => "edit_others_$plural",
            'publish_posts'      => "publish_$plural",
            'read_private_posts'     => "read_private_$plural",
            'read'                   => "read",
            'delete_posts'           => "delete_$plural",
            'delete_private_posts'   => "delete_private_$plural",
            'delete_published_posts' => "delete_published_$plural",
            'delete_others_posts'    => "delete_others_$plural",
            'edit_private_posts'     => "edit_private_$plural",
            'edit_published_posts'   => "edit_published_$plural",
            'create_posts'           => "create_$plural",
        ];
    }

    /**
     * @param string $singular
     * @param string $plural
     *
     * @return string[]
     */
    public function getTaxonomyCapabilities($singular = 'term', $plural = 'terms')
    {
        return [
            'manage_terms' => 'manage_' . $plural,
            'edit_terms'   => 'edit_' . $plural,
            'delete_terms' => 'delete_' . $plural,
            'assign_terms' => 'assign_' . $plural,
        ];
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }
}