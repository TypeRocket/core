<?php
namespace TypeRocket\Services;

use TypeRocket\Auth\Policy;
use TypeRocket\Controllers\Controller;
use TypeRocket\Core\Resolver;
use TypeRocket\Models\AuthUser;
use TypeRocket\Models\Model;

class AuthorizerService extends Service
{
    public const ALIAS = 'auth';
    protected $policies = [];

    /**
     * AuthorizerService constructor.
     *
     * @param null $policies
     */
    public function __construct($policies = null)
    {
        if(is_array($policies)) {
            $this->policies = $policies;
        }
    }

    /**
     * Auth Registered Policy
     *
     * This is used for models.
     *
     * @param AuthUser $user
     * @param Model $model
     * @param string $action
     *
     * @return mixed
     * @throws \Exception
     */
    public function authRegistered(AuthUser $user, Model $model, $action)
    {
        $policy = null;
        $pass = false;
        $modelClass = '\\' . get_class($model);

        if(!array_key_exists($modelClass, $this->policies)) {
            if($parent_classes = class_parents($model)) {
                foreach ($parent_classes as $parent_class) {
                    if(array_key_exists('\\' . $parent_class, $this->policies)) {
                        $modelClass = '\\' . $parent_class;
                        break;
                    }
                }
            }
        }

        if(array_key_exists($modelClass, $this->policies)) {
            $policy = (new Resolver)->resolve($this->policies[$modelClass]);
        }

        if($policy && method_exists($policy, $action)) {
            $pass = $policy->{$action}($user, $model);
        }

        return apply_filters('typerocket_auth_policy_check', $pass, $policy, 'authRegistered');
    }

    /**
     * Auth
     *
     * @param AuthUser $user
     * @param Policy|Model|Controller|object|string $option
     * @param string $action
     * @param null|string|Policy $policy
     *
     * @return mixed
     * @throws \Exception
     */
    public function auth(AuthUser $user, $option, $action, $policy = null)
    {
        $pass = false;

        if(is_string($option)) {
            $option = (new Resolver)->resolve($option);
        }

        if(is_string($policy)) {
            $policy = (new Resolver)->resolve($policy);
        }

        $policy = $policy ?? ($option instanceof Policy ? $option : null);
        $class = '\\' . get_class($option);

        if(!$policy && !array_key_exists($class, $this->policies)) {
            $class = '\\' . get_parent_class($option);
        }

        if(!$policy && array_key_exists($class, $this->policies)) {
            $policy = (new Resolver)->resolve($this->policies[$class]);
        }

        if($policy && method_exists($policy, $action)) {
            $pass = $policy->{$action}($user, $option);
        }

        return apply_filters('typerocket_auth_policy_check', $pass, $policy, 'auth');
    }

}