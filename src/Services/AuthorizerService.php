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
     * @param null|array $policies
     */
    public function __construct($policies = null)
    {
        if(is_array($policies)) {
            $this->policies = $policies;
        }
    }

    /**
     * Get Policies
     *
     * @return mixed|void
     */
    public function getPolicies()
    {
        return apply_filters('typerocket_auth_policies', $this->policies, $this);
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
        $policies = $this->getPolicies();

        if(!array_key_exists($modelClass, $policies)) {
            if($parent_classes = class_parents($model)) {
                foreach ($parent_classes as $parent_class) {
                    if(array_key_exists('\\' . $parent_class, $policies)) {
                        $modelClass = '\\' . $parent_class;
                        break;
                    }
                }
            }
        }

        if(array_key_exists($modelClass, $policies)) {
            $policy = (new Resolver)->resolve($policies[$modelClass]);
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

        $policies = $this->getPolicies();
        $policy = $policy ?? ($option instanceof Policy ? $option : null);

        if(!$policy) {
            $class = '\\' . get_class($option);

            if(!array_key_exists($class, $policies)) {
                if($parent_classes = class_parents($option)) {
                    foreach ($parent_classes as $parent_class) {
                        if(array_key_exists('\\' . $parent_class, $policies)) {
                            $class = '\\' . $parent_class;
                            break;
                        }
                    }
                }
            }

            if(array_key_exists($class, $policies)) {
                $policy = (new Resolver)->resolve($policies[$class]);
            }
        }

        if($policy && method_exists($policy, $action)) {
            $pass = $policy->{$action}($user, $option);
        }

        return apply_filters('typerocket_auth_policy_check', $pass, $policy, 'auth');
    }

}