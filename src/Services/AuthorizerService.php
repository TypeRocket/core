<?php
namespace TypeRocket\Services;

use TypeRocket\Auth\Policy;
use TypeRocket\Controllers\Controller;
use TypeRocket\Core\Resolver;
use TypeRocket\Models\AuthUser;
use TypeRocket\Models\Model;

class AuthorizerService extends Service
{
    protected $alias = 'auth';
    protected $policies = [];

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
        $modelClass = '\\' . get_class($model);

        if(!array_key_exists($modelClass, $this->policies)) {
            $modelClass = '\\' . get_parent_class($model);
        }

        if(array_key_exists($modelClass, $this->policies)) {
            $policy = (new Resolver)->resolve($this->policies[$modelClass]);
        }

        if($policy && method_exists($policy, $action)) {
            return $policy->{$action}($user, $model);
        }

        throw new \Exception("Policy is not registered or it's action does not exist.");
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
            return $policy->{$action}($user, $option);
        }

        throw new \Exception("Policy or it's action does not exist.");
    }

}