<?php

namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
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

        // Make directories if needed
        if( ! file_exists( TR_PATH . '/sql' ) ) {
            mkdir(TR_PATH . '/sql', 0755, true);
        }

        if( ! file_exists( TR_PATH . '/sql/migrations' ) ) {
            mkdir(TR_PATH . '/sql/migrations', 0755, true);
        }

        // Make migration file
        $tags = ['{{name}}'];
        $replacements = [ $name ];
        $template = __DIR__ . '/../../../templates/Migration.txt';
        $new = TR_PATH . '/sql/migrations/' . time() . '.' . $name . ".sql";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success('Migration created: ' . $name );
        } else {
            $this->error('TypeRocket migration ' . $name . ' exists.');
        }

    }
}