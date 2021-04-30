<?php
namespace TypeRocket\Core
{
    use TypeRocket\Models\AuthUser;
    use TypeRocket\Models\WPUser;
    use TypeRocket\Services\Service;
    use TypeRocket\Utility\RuntimeCache;

    class ApplicationKernel
    {
        protected $loaded = false;

        /**
         * Init Application
         */
        public static function init()
        {
            $app = new static;
            $app->boot();

            if (defined('WPINC')) {
                $app->default();
            }
            elseif(!defined('TYPEROCKET_GALAXY')) {
                $app->root();
            }
            else {
                $app->galaxy();
            }
        }

        /**
         * Boot for default setup
         */
        public function default()
        {
            $this->loadServices();
            (new System)->boot();
        }

        /**
         * Boot for root install
         */
        public function root()
        {
            if(!defined('TYPEROCKET_ROOT_INSTALL'))
                define('TYPEROCKET_ROOT_INSTALL', true);

            $this->bootSystemAfterMustUseLoaded();
        }

        /**
         * Boot for Galaxy CLI
         */
        public function galaxy()
        {
            $this->bootSystemAfterMustUseLoaded();
        }

        /**
         * Boot after MU plugins loaded
         */
        public function bootSystemAfterMustUseLoaded()
        {
            static::addFilter('muplugins_loaded', function() {
                $this->loadServices();

                if( is_file(TYPEROCKET_ALT_PATH . '/rooter.php') ) {
                    require(TYPEROCKET_ALT_PATH . '/rooter.php');
                }

                (new System)->boot();
                (new Rooter)->boot();
            }, 0, 0);
        }

        /**
         * Boot Container
         */
        public function boot()
        {
            if($this->loaded) {
                return $this;
            }

            $this->loaded = true;

            Container::singleton(Config::class, function() {
                return new Config(TYPEROCKET_CORE_CONFIG_PATH);
            }, Config::ALIAS);

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

            return $this;
        }

        /**
         * @throws \ReflectionException
         */
        public function loadServices()
        {
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
        }

        /**
         * @param string $tag
         * @param callable $function
         * @param int $priority
         * @param int $accepted_args
         */
        public static function addFilter(string $tag, callable $function, $priority = 10, $accepted_args = 1)
        {
            if(function_exists('add_filter')) {
                add_filter(...func_get_args());
            } else {
                $GLOBALS['wp_filter'][$tag][$priority]['callbacks'] = ['function' => $function, 'accepted_args' => $accepted_args];
            }
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
     * @param bool $env Try getting env data
     *
     * @return mixed
     */
    function typerocket_env($name, $default = null, $env = false) {
        return \TypeRocket\Core\Config::env($name, $default, $env);
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