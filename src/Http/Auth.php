<?php
namespace TypeRocket\Http;

use Exception;
use TypeRocket\Auth\Policy;
use TypeRocket\Core\Container;
use TypeRocket\Models\AuthUser;
use TypeRocket\Services\AuthorizerService;

class Auth
{
    public const ALIAS = 'auth';

    /**
     * @param string $action
     * @param object|string $option
     * @param null|AuthUser $user
     * @param Policy|string|null $policy
     *
     * @return mixed
     * @throws Exception
     */
    public static function action($action, $option, $user = null, $policy = null) {
        /** @var AuthorizerService  $auth */
        $auth = Container::resolveAlias(static::ALIAS);

        if(!$user) {
            $user = Container::resolveAlias(AuthUser::ALIAS);
        }

        return $auth->auth($user, $option, $action, $policy);
    }
}