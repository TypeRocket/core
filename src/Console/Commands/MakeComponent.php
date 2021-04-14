<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Str;

class MakeComponent extends Command
{
    protected $command = [
        'make:component',
        'Make new component',
        'This command makes new component for the builder and matrix fields.',
    ];

    protected function config()
    {
        $this->addArgument('key', self::REQUIRED, 'The registered component config key.');
        $this->addArgument('class', self::OPTIONAL, 'The component class name.');
        $this->addArgument('title', self::OPTIONAL, 'The component title.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:command MyCommandClass space:name
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $command = $this->getClassArgument('class');
        $title = $this->getArgument('title');
        $key = $this->getArgument('key');

        if(!$title) {
            $title = ucwords($key);
        }

        $key = Sanitize::underscore($key);

        if(!$command) {
            $command = Str::camelize($key);
        }

        [$namespace, $class] = Str::splitAt('\\', $command, true);
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Components', $namespace]));

        $tags = ['{{namespace}}', '{{component}}', '{{title}}'];
        $replacements = [ $namespace, $class, $title ];
        $template = __DIR__ . '/../../../templates/Component.txt';

        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $command_file = $app_path . '/Components/' . str_replace("\\",'/', $command) . ".php";
        $command_path = substr($command_file, 0, -1 + -strlen(basename($command_file)) ) ;

        if( ! file_exists( $command_path ) ) {
            mkdir($command_path, 0755, true);
        }

        if( ! file_exists( $app_path . '/Components' ) ) {
            mkdir($app_path . '/Components', 0755, true);
        }

        $file = new File( $template );
        $command_file = $file->copyTemplateFile( $command_file, $tags, $replacements );

        if( $command_file ) {
            $this->success('Component created: ' . $command );

            $file = new File(TYPEROCKET_CORE_CONFIG_PATH . '/components.php');

            if($file->exists()) {
                $eol = PHP_EOL;
                $new = "        '{$key}' => \\$namespace\\$class::class,{$eol}";
                if(!$file->replaceOnLine("'registry' => [", "'registry' => [{$eol}{$new}")) {
                    $this->warning('Register your new component ' . $command . ' to config/components.php' );
                };
            }

            $this->warning('Add your new component ' . $command . ' to a group in config/components.php' );
            $this->warning('Add a thumbnail for your component: ' . \TypeRocket\Core\Config::get('urls.components') . '/' . Sanitize::underscore($command) . '.png' );
        } else {
            $this->error('TypeRocket ' . $command . ' exists.');
        }
    }
}