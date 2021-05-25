<?php
namespace TypeRocket\Console;

use Exception;
use Symfony\Component\Console\Application;
use TypeRocket\Core\ApplicationKernel;
use TypeRocket\Core\Config;
use TypeRocket\Core\System;

class GalaxyConsoleLauncher
{
    public $wordpress;
    public $advanced;
    public $commands;
    public $console;
    public $loaded = false;
    /** @var \TypeRocket\Core\Config */
    public $config;
    public $wpRoot;

    /**
     * Launch CLI
     *
     * Launcher constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->console = new Application();
        $this->commands = new CommandCollection();
        $this->commands->configure(Config::getFromContainer());
        $wp_root = \TypeRocket\Core\Config::get('galaxy.wordpress');
        $wp_root_load = \TypeRocket\Core\Config::get('galaxy.wordpress_load', true);

        // Set common headers, to prevent warnings from plugins.
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
        $_SERVER['HTTP_USER_AGENT'] = '';
        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_SERVER['REMOTE_ADDR']     = '127.0.0.1';

        if( !empty($wp_root) && $wp_root_load !== 'no') {
            $this->wpRoot = $wp_root = realpath($wp_root);
            $is_file = is_file( $wp_root . '/wp-load.php' );
            $has_config = is_file( $wp_root . '/wp-config.php' );
            $has_config = $has_config ?: is_file( $wp_root . '/../wp-config.php' );

            if($has_config && $is_file) {
                $this->wordpress = true;

                if(class_exists(System::ADVANCED)) {
                    $this->advanced = true;
                }

                // bypass maintenance mode
                ApplicationKernel::addFilter('enable_maintenance_mode', function() {return false;}, 0, 0);
                // wp filters and actions are the same thing
                ApplicationKernel::addFilter('after_setup_theme', [$this, 'loadWordPressFunctions'], 28, 0);
                ApplicationKernel::addFilter('after_setup_theme', [$this, 'loadCommandsAndRun'], 30, 0);

                define('WP_USE_THEMES', true);
                global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;

                /** @noinspection PhpIncludeInspection */
                require( $wp_root . '/wp-load.php');

                add_filter('enable_maintenance_mode', function(){
                    return true;
                });
            }
            else {
                echo $is_file ? '' : 'WP root path might not be not correct:' . realpath($wp_root) .PHP_EOL;
                echo $has_config ? '' : 'wp-config.php not found'.PHP_EOL;
                echo "\033[0;31mWP Commands not enabled.\033[0m".PHP_EOL;
            }
        }

        if($wp_root_load === 'no') {
            echo "\033[0;31mWP loading is disabled in your Galaxy CLI config.\033[0m".PHP_EOL;
        }

        $this->loadCommandsAndRun();
    }

    /**
     * Load WordPress Functions
     */
    public function loadWordPressFunctions()
    {
        /** @noinspection PhpIncludeInspection */
        require_once( $this->wpRoot . '/wp-admin/includes/upgrade.php' );
    }

    /**
     * @throws Exception
     */
    public function loadCommandsAndRun()
    {
        if($this->loaded) {
            return;
        }

        $this->commands->enableCustom();

        if($this->wordpress) {
            $this->commands->enableWordPress();
        }

        if($this->advanced) {
            $this->commands->enableAdvanced();
        }

        foreach ($this->commands as $command ) {
            $this->console->add( new $command );
        }

        $this->console->run();
        $this->loaded = true;
    }
}