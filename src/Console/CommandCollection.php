<?php

namespace TypeRocket\Console;

class CommandCollection extends \ArrayObject
{
    public $commands = [
        Commands\MakeController::class,
        Commands\MakeMiddleware::class,
        Commands\MakeCommand::class,
        Commands\MakeModel::class,
        Commands\GenerateSeed::class,
        Commands\UseTemplates::class,
        Commands\UseRoot::class,
    ];

    public $wordpress = [
        Commands\FlushRewrites::class,
        Commands\SQL::class,
        Commands\MakeMigration::class,
        Commands\Migrate::class
    ];

    /**
     * Load commands
     */
    public function __construct() {
        $this->exchangeArray($this->commands);
    }

    /**
     * Enable WordPress Commands
     */
    public function enableWordPress()
    {
        foreach ( $this->wordpress as $command ) {
            $this->append($command);
        }
    }

    /**
     * Enable custom commands
     */
    public function enableCustom()
    {
        $commands = Config::getCommands();
        if( $commands) {
            foreach ( $commands as $command ) {
                $this->append($command);
            }
        }

    }

}