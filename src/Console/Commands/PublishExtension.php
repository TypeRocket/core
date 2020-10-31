<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Input\InputOption;
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
        $this->addArgument('submodule', self::OPTIONAL, 'The package submodule to publish.');
        $this->addOption('mode', 'M', InputOption::VALUE_OPTIONAL, 'Modes of publishing: publish or unpublish', 'publish');
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
        // $this->confirm('Publishing can be destructive... Continue anyways? (y/n)');
        $package = trim($this->getArgument('package'), '\\/');
        $script = ltrim($this->getArgument('submodule', 'publish'), '/.\\');

        if(is_file($path = TYPEROCKET_PATH . '/vendor/'.$package.'/ext/'.$script.'.php')) {
            /** @noinspection PhpIncludeInspection */
            $cb = function($path) { require_once $path; };
            $cb($path);
            $this->success(sprintf('Package %s published with %s', $package, $path));
        } else {
            $this->error('Package script not found: ' . $path);
        }
    }
}