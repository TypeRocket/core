<?php

namespace TypeRocket\Console;

use Symfony\Component\Console\Application;

class Launcher
{

    public function __construct()
    {
        $application = new Application();
        $commands = new CommandCollection();
        foreach ($commands as $command ) {
            $application->add( new $command );
        }
        $application->run();
    }

}