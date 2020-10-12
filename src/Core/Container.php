<?php
namespace TypeRocket\Core;

use TypeRocket\Http\RouteCollection;
use TypeRocket\Models\AuthUser;
use TypeRocket\Models\WPUser;
use TypeRocket\Services\Service;
use TypeRocket\Utility\RuntimeCache;

class Container
{
    /**
     * Boot Container
     */
    public function boot()
    {
        // Initial singletons
        Injector::singleton(Config::class, function() {
            return new Config(TR_CORE_CONFIG_PATH);
        }, Config::ALIAS);

        if(immutable('TR_ROUTES', true) ) {
            Injector::singleton(RouteCollection::class, function() {
                return new RouteCollection();
            }, RouteCollection::ALIAS);
        }

        Injector::singleton(RuntimeCache::class, function() {
            return new RuntimeCache();
        }, RuntimeCache::ALIAS);

        Injector::singleton(AuthUser::class, function() {
            $user_class = tr_app_class('Models\User');
            /** @var WPUser $user */
            $user = (new $user_class);

            try {
                $wp_user = wp_get_current_user();
            } catch (\Throwable $e) {
                throw new \Exception('AuthUser class is not accessible until `plugins_loaded` action has fired');
            }
            $user->wpUser($wp_user);

            return $user;
        }, 'user');

        // Application Services
        $services = tr_config('app.services');

        /**
         * @var string[] $services
         */
        foreach ($services as $service) {
            $instance = (new Resolver)->resolve($service);
            if($instance instanceof Service) {
                Injector::register($service, [$instance, 'register'], $instance->isSingleton(), $instance->alias());
            }
        }

        return $this;
    }
}