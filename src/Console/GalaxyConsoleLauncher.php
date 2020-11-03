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

        // Set common headers, to prevent warnings from plugins.
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
        $_SERVER['HTTP_USER_AGENT'] = '';
        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_SERVER['REMOTE_ADDR']     = '127.0.0.1';

        if( !empty($wp_root) ) {
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
                ApplicationKernel::addFilter('after_setup_theme', [$this, 'loadCommandsAndRun'], 30, 0);

                define('WP_USE_THEMES', true);
                global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
                /** @noinspection PhpIncludeInspection */
                require( $wp_root . '/wp-load.php');
                /** @noinspection PhpIncludeInspection */
                require( $wp_root . '/wp-admin/includes/upgrade.php' );

                add_filter('enable_maintenance_mode', function(){
                    return true;
                });
            }
            else {
                echo $is_file ? '' : 'WP root path might not be not correct:' . realpath($wp_root) .PHP_EOL;
                echo $has_config ? '' : 'wp-config.php not found'.PHP_EOL;
                echo 'WP Commands not enabled.'.PHP_EOL;
            }
        } else {
            $this->loadCommandsAndRun();
        }
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