<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Str;

class MakeCommand extends Command
{
    protected $command = [
        'make:command',
        'Make new command',
        'This command makes new galaxy commands.',
    ];

    protected function config()
    {
        $this->addArgument('class', self::REQUIRED, 'The command class name.');
        $this->addArgument('name', self::REQUIRED, 'The command name used by galaxy.');
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
        $name = strtolower( $this->getArgument('name') );

        [$namespace, $class] = Str::splitAt('\\', $command, true);
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Commands', $namespace]));

        $tags = ['{{namespace}}', '{{command}}', '{{name}}'];
        $replacements = [ $namespace, $class, $name ];
        $template = __DIR__ . '/../../../templates/Command.txt';

        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $command_file = $app_path . '/Commands/' . str_replace("\\",'/', $command) . ".php";
        $command_path = substr($command_file, 0, -1 + -strlen(basename($command_file)) ) ;

        if( ! file_exists( $command_path ) ) {
            mkdir($command_path, 0755, true);
        }

        if( ! file_exists( $app_path . '/Commands' ) ) {
            mkdir($app_path . '/Commands', 0755, true);
        }

        $file = new File( $template );
        $command_file = $file->copyTemplateFile( $command_file, $tags, $replacements );

        if( $command_file ) {
            $this->success('Command created: ' . $command );

            $file = new File(TYPEROCKET_CORE_CONFIG_PATH . '/galaxy.php');

            if($file->exists()) {
                $eol = PHP_EOL;
                $new = "        \\$namespace\\$class::class,{$eol}";

                if(!$file->replaceOnLine("'commands' => [", "'commands' => [{$eol}{$new}")) {
                    $this->warning('Register your new command ' . $command . ' to config/galaxy.php' );
                };
            }
        } else {
            $this->error('TypeRocket ' . $command . ' exists.');
        }
    }
}