<?php
namespace TypeRocket\Core
{
    use TypeRocket\Http\RouteCollection;
    use TypeRocket\Models\AuthUser;
    use TypeRocket\Models\WPUser;
    use TypeRocket\Services\Service;
    use TypeRocket\Utility\RuntimeCache;

    class ApplicationKernel
    {
        /**
         * Boot Container
         */
        public function boot()
        {
            // Initial singletons
            Container::singleton(Config::class, function() {
                return new Config(TYPEROCKET_CORE_CONFIG_PATH);
            }, Config::ALIAS);

            if(Config::env('TYPEROCKET_ROUTES', true) ) {
                Container::singleton(RouteCollection::class, function() {
                    return new RouteCollection();
                }, RouteCollection::ALIAS);
            }

            Container::singleton(RuntimeCache::class, function() {
                return new RuntimeCache();
            }, RuntimeCache::ALIAS);

            Container::singleton(AuthUser::class, function() {
                $user_class = \TypeRocket\Utility\Helper::appNamespace('Models\User');
                /** @var WPUser $user */
                $user = (new $user_class);

                try {
                    $wp_user = wp_get_current_user();
                } catch (\Throwable $e) {
                    throw new \Exception('AuthUser class is not accessible until `plugins_loaded` action has fired');
                }
                $user->wpUser($wp_user);

                return $user;
            }, AuthUser::ALIAS);

            // Application Services
            $services = Config::get('app.services');

            /**
             * @var string[] $services
             */
            foreach ($services as $service) {
                $instance = (new Resolver)->resolve($service);
                if($instance instanceof Service) {
                    Container::register($service, [$instance, 'register'], $instance->isSingleton(), $instance::ALIAS);
                }
            }

            return $this;
        }

        /**
         * Auto loader
         *
         * Array keys include: `init`, `map`, `prefix`, and `folder`
         *
         * @param array $map
         * @param bool $prepend If true, will prepend the autoloader on the autoload stack
         */
        public static function autoloadPsr4(array &$map = [], $prepend = false)
        {
            if (isset($map['init'])) {
                foreach ($map['init'] as $file) {
                    require $file;
                }
            }
            spl_autoload_register(function ($class) use (&$map) {
                if (isset($map['map'][$class])) {
                    require $map['map'][$class];
                    return;
                }
                $prefix = $map['prefix'];
                $folder = $map['folder'];
                $len = strlen($prefix);
                if (strncmp($prefix, $class, $len) !== 0) {
                    return;
                }
                $file = $folder . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $len)) . '.php';
                if (is_file($file)) {
                    require $file;
                    return;
                }
            }, true, $prepend);
        }
    }
}

namespace
{
    use TypeRocket\Core\ApplicationKernel;

    /**
     * Get Constant Variable
     *
     * @param string $name the constant variable name
     * @param null|mixed $default The default value
     *
     * @return mixed
     */
    function typerocket_env($name, $default = null) {
        return \TypeRocket\Core\Config::env($name, $default);
    }

    /**
     * Auto loader
     *
     * Array keys include: `init`, `map`, `prefix`, and `folder`
     *
     * @param array $map
     * @param bool $prepend If true, will prepend the autoloader on the autoload stack
     */
    function typerocket_autoload_psr4(array &$map = [], $prepend = false)
    {
        ApplicationKernel::autoloadPsr4(...func_get_args());
    }
}