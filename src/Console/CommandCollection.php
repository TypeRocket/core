<?php

namespace TypeRocket\Console;

class CommandCollection extends \ArrayObject
{
    public $commands = [
        Commands\MakeController::class,
        Commands\MakeMiddleware::class,
        Commands\MakeModel::class,
        Commands\GenerateSeed::class,
        Commands\UseTemplates::class,
        Commands\UseRoot::class,
    ];

    public $wordpress = [
        Commands\FlushRewrites::class
    ];

    /**
     * Load icons and their font encoding
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

    public function enableCustom()
    {
        $custom = Config::getCommands();

        foreach ( $custom as $command ) {
            $this->append($command);
        }
    }

}