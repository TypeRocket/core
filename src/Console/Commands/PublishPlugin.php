<?php
namespace TypeRocket\Console\Commands;


use TypeRocket\Console\Command;

class PublishPlugin extends Command
{
    protected $command = [
        'plugin:publish',
        'Publish plugin',
        'This command publishes a plugin install via composer.',
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
        $package = $this->getArgument('package');

        if(file_exists($path = $package . '/publish.php')) {
            require_once $package . '/publish.php';
        }
        elseif(file_exists($path = TR_PATH . '/vendor/'.$package.'/publish.php')) {
            require_once TR_PATH . '/vendor/'.$package.'/publish.php';
        } else {
            $this->error('Package not found.' . $path);
            die();
        }

        $this->success(sprintf('Package %s published', $package));
    }
}