<?php

namespace TypeRocket\Console;

use Exception;
use Symfony\Component\Console\Application;
use TypeRocket\Core\Config;

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
        $wp_root = Config::locate('galaxy.wordpress');

        if( !empty($wp_root) ) {
            $is_file = is_file( $wp_root . '/wp-load.php' );

            if(!$is_file) {
                throw new Exception('WP root path is not correct:' . realpath($wp_root) );
            }

            define('WP_USE_THEMES', true);
            global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
            require( $wp_root . '/wp-load.php' );
            require( $wp_root . '/wp-admin/includes/upgrade.php' );

            $commands->enableWordPress();
        }

        foreach ($commands as $command ) {
            $application->add( new $command );
        }
        $application->run();
    }

}