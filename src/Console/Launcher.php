<?php

namespace TypeRocket\Console;

use Symfony\Component\Console\Application;

class Launcher
{

    public function __construct()
    {
        $application = new Application();
        $commands = new CommandCollection();
        $commands->enableCustom();

        if( file_exists( Config::getWordPressPath() . '/wp-load.php' ) ) {
            define('WP_USE_THEMES', true);
            global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
            require( Config::getWordPressPath() . '/wp-load.php' );
            require( Config::getWordPressPath() . '/wp-admin/includes/upgrade.php' );

            $commands->enableWordPress();
        }

        foreach ($commands as $command ) {
            $application->add( new $command );
        }
        $application->run();
    }

}