<?php

namespace TypeRocket\Console\Commands;

use TypeRocket\Console\CanQueryDB;
use TypeRocket\Console\Command;

class SQL extends Command
{
    use CanQueryDB;

    protected $command = [
        'wp:sql',
        'WordPress database SQL script',
        'This command runs a WordPress database SQL script.',
    ];

    protected function config()
    {
        $this->addArgument('name', self::REQUIRED, 'The name of the SQL script to run.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy wp:sql my_script
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $name = $this->getArgument('name');
        $file_sql = TR_PATH . '/sql/' . $name . '.sql';
        $this->runQueryFile($file_sql);
    }

    protected function sqlSuccess($message) {
        $name = $this->getArgument('name');
        $this->success('SQL '. $name .' successfully run.');
    }

    protected function sqlError($message) {
        $name = $this->getArgument('name');
        $this->error('Query Error: SQL '. $name .' failed to run.');
    }
}