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

        if( file_exists( Config::getWordPressPath() ) ) {
            define('WP_USE_THEMES', true);
            global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
            require( Config::getWordPressPath() );

            $commands->enableWordPress();
        }

        foreach ($commands as $command ) {
            $application->add( new $command );
        }
        $application->run();
    }

}