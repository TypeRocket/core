<?php
namespace TypeRocket\Console;

use TypeRocket\Core\Config;

class CommandCollection extends \ArrayObject
{
    /** @var Config */
    public $config;

    public $commands = [
        Commands\MakeController::class,
        Commands\MakeComponent::class,
        Commands\MakeMiddleware::class,
        Commands\MakeCommand::class,
        Commands\MakeFields::class,
        Commands\MakeComposer::class,
        Commands\MakePolicy::class,
        Commands\MakeRule::class,
        Commands\MakeService::class,
        Commands\MakeModel::class,
        Commands\GenerateSeed::class,
        Commands\RootInstall::class,
        Commands\PublishExtension::class,
        Commands\ClearCache::class,
        Commands\CoreUpdate::class,
        Commands\DownloadWordPress::class,
    ];

    public $wordpress = [
        Commands\SQL::class,
        Commands\MakeMigration::class,
        Commands\Migrate::class,
    ];

    /**
     * Load commands
     */
    public function __construct() {
        parent::__construct();
        $this->exchangeArray($this->commands);
    }

    /**
     * @param Config $config
     *
     * @return $this
     */
    public function configure(Config $config)
    {
        $this->config = $config;

        return $this;
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
        $commands = $this->config->locate('galaxy.commands');
        if( $commands) {
            foreach ( $commands as $command ) {
                $this->append($command);
            }
        }
    }

    /**
     * Enable custom commands
     */
    public function enableAdvanced()
    {
        $file = $this->config->locate('paths.pro') . '/commands.php';

        if(file_exists($file)) {
            $commands = require($file);
        } else {
            echo 'Can not load pro commands... ' . $file . PHP_EOL;
        }

        if( $commands) {
            foreach ( $commands as $command ) {
                $this->append($command);
            }
        }
    }

}
