<?php
namespace TypeRocket\Console;

use Exception;
use Symfony\Component\Console\Application;
use TypeRocket\Core\System;

class Launcher
{
    /**
     * Launch CLI
     *
     * Launcher constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $application = new Application();
        $commands = new CommandCollection();
        $commands->enableCustom();
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

            if($has_config) {

                if(!$is_file) {
                    throw new Exception('WP root path is not correct:' . realpath($wp_root) );
                }

                // bypass maintenance mode
                $GLOBALS['wp_filter']['enable_maintenance_mode'] = ['callbacks' => [ ['function' => function() {return false;}]] ];

                define('WP_USE_THEMES', true);
                global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
                /** @noinspection PhpIncludeInspection */
                require( $wp_root . '/wp-load.php');
                /** @noinspection PhpIncludeInspection */
                require( $wp_root . '/wp-admin/includes/upgrade.php' );

                add_filter('enable_maintenance_mode', function(){
                    return true;
                });

                $commands->enableWordPress();

                if(class_exists(System::ADVANCED)) {
                    $commands->enableAdvanced();
                }
            } else {
                echo 'WP Commands not enabled: wp-config.php not found'.PHP_EOL;
            }
        }

        foreach ($commands as $command ) {
            $application->add( new $command );
        }
        $application->run();
    }

}