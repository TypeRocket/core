<?php
namespace TypeRocket\Console;

class CommandCollection extends \ArrayObject
{
    public $commands = [
        Commands\MakeController::class,
        Commands\MakeComponent::class,
        Commands\MakeMiddleware::class,
        Commands\MakeCommand::class,
        Commands\MakeFields::class,
        Commands\MakePolicy::class,
        Commands\MakeService::class,
        Commands\MakeModel::class,
        Commands\GenerateSeed::class,
        Commands\RootMuPluginInstall::class,
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
        $commands = \TypeRocket\Core\Config::get('galaxy.commands');
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
        $file = \TypeRocket\Core\Config::get('paths.pro') . '/commands.php';

        if(file_exists($file)) {
            $commands = include($file);
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
