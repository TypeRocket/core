<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Database\SqlRunner;
use TypeRocket\Exceptions\SqlException;

class SQL extends Command
{
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
        $file_sql = TYPEROCKET_PATH . '/sql/' . $name . '.sql';
        $this->warning("Running {$file_sql}:" );

        try {
            (new SqlRunner())->runQueryFile($file_sql, function($report) use ($file_sql) {
                $this->success($report['message']);
                $this->line($report['wpdb']);
            });
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            if($e instanceof SqlException) {
                $this->warning('Failed SQL:' );
                $this->line( $e->getSql() );
                $this->error( $e->getSqlError() );
            }
        }
    }
}