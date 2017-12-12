<?php

namespace TypeRocket\Console;

use Symfony\Component\Console\Application;
use TypeRocket\Core\Config;

class Launcher
{

    public function __construct()
    {
        $application = new Application();
        $commands = new CommandCollection();
        $commands->enableCustom();
        $wp_root = Config::locate('galaxy.wordpress');

        if( is_file( $wp_root . '/wp-load.php' ) ) {
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