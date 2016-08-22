<?php

namespace TypeRocket\Console\Commands;


use TypeRocket\Console\Command;
use TypeRocket\Utility\File;

class MakeCommand extends Command
{
    protected $command = [
        'make:command',
        'Make new command',
        'This command allows you to make new galaxy commands.',
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
        $command = $this->getArgument('class');
        $name = strtolower( $this->getArgument('name') );

        if( ! file_exists( TR_PATH . '/app/Commands' ) ) {
            mkdir(TR_PATH . '/app/Commands', 0755, true);
        }

        $tags = ['{{namespace}}', '{{command}}', '{{name}}'];
        $replacements = [ TR_APP_NAMESPACE, $command, $name ];
        $template = __DIR__ . '/../../../templates/Command.txt';
        $new = TR_PATH . '/app/Commands/' . $command . ".php";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success('Command created: ' . $command );
            $this->warning('Configure Command ' . $command . ': Add your command to config/galaxy.php' );
        } else {
            $this->error('TypeRocket ' . $command . ' exists.');
        }

    }
}