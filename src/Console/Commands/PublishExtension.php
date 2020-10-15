<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;

class PublishExtension extends Command
{
    protected $command = [
        'extension:publish',
        'Publish extension package',
        'This command publishes an extension package installed via composer.',
    ];

    protected function config()
    {
        $this->addArgument('package', self::REQUIRED, 'The package: vendor/package or path.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:controller base member
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $this->confirm('Publishing can be destructive... Continue anyways? (y/n)');
        $package = $this->getArgument('package');

        if(file_exists($path = $package . '/publish.php')) {
            /** @noinspection PhpIncludeInspection */
            require_once $package . '/publish.php';
        }
        elseif(file_exists($path = TYPEROCKET_PATH . '/vendor/'.$package.'/publish.php')) {
            /** @noinspection PhpIncludeInspection */
            require_once TYPEROCKET_PATH . '/vendor/'.$package.'/publish.php';
        } else {
            $this->error('Package not found.' . $path);
            die();
        }

        $this->success(sprintf('Package %s published', $package));
    }
}