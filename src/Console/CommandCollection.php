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

    /**
     * Load icons and their font encoding
     */
    public function __construct() {
        $this->exchangeArray($this->commands);
    }

}