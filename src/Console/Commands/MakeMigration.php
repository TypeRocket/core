<?php

namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Core\Config;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Sanitize;

class MakeMigration extends Command
{
    protected $command = [
        'make:migration',
        'Make new migration',
        'This command allows you to make new SQL migrations.',
    ];

    protected function config()
    {
        $this->addArgument('name', self::REQUIRED, 'The migration name.');
        $this->addArgument('index', self::OPTIONAL, 'The the array index of the migration folder to use.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:migration name_of_migration
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $name = Sanitize::underscore( $this->getArgument('name') );
        $index = $this->getArgument('index') ?: 0;
        $root = Config::locate('paths.migrate.migrations');
        $root = is_array($root) ? $root[$index] : $root;

        // Make directories if needed
        if( ! file_exists($root) ) {
            $this->warning('TypeRocket trying to locate ' . $root . ' for migrations.');
            mkdir($root, 0755, true);
            $this->success('Location created...');
        }

        // Make migration file
        $tags = ['{{name}}'];
        $replacements = [ $name ];
        $template = __DIR__ . '/../../../templates/Migration.txt';
        $new = $root . '/' . time() . '.' . $name . ".sql";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success('Migration created: ' . $name );
        } else {
            $this->error('TypeRocket migration ' . $name . ' exists.');
        }

    }
}