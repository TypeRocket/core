<?php

namespace TypeRocket\Console\Commands;

use TypeRocket\Console\CanQueryDB;
use TypeRocket\Console\Command;
use TypeRocket\Utility\File;

class Migrate extends Command
{
    use CanQueryDB;

    protected $command = [
        'migrate',
        'Run migrations',
        'This command runs migrations.',
    ];

    protected function config()
    {
        $this->addArgument('type', self::REQUIRED, 'The type of migration to run (up|down).');
        $this->addArgument('steps', self::OPTIONAL, 'The limit of migrations to run as int.');
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
        $type = $this->getArgument('type');
        $steps = $this->getArgument('steps');

        if(!$steps && $type == 'up') {
            $steps = 99999999999999;
        }

        if(!$steps) {
            $steps = 1;
        }

        $this->sqlMigrationDirectory($type, $steps);
    }

    protected function sqlMigrationDirectory($type, $steps = 1) {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $migrations_folder = TR_PATH . '/sql/migrations/';
        $migrations_run_folder = TR_PATH . '/sql/run/';

        if( ! file_exists( $migrations_folder ) ) {
            $this->error('No migrations found at: ' . $migrations_folder);
            return;
        }

        // Make directories if needed
        if( ! file_exists( $migrations_run_folder ) ) {
            mkdir($migrations_run_folder, 0755, true);
        }

        $migrations = array_diff(scandir($migrations_folder), ['..', '.'] );
        $migrations_run = array_diff(scandir($migrations_run_folder), ['..', '.'] );

        if($type == 'up') {
            $to_run = array_diff($migrations, $migrations_run);
            $match_go = '/--\s+\>\>\>\s+Up\s+\>\>\>/';
            $match_stop = '/--\s+\>\>\>\s+Down\s+\>\>\>/';
        } else {
            $to_run = array_reverse($migrations_run);
            $match_go = '/--\s+\>\>\>\s+Down\s+\>\>\>/';
            $match_stop = '/--\s+\>\>\>\s+Up\s+\>\>\>/';
        }


        $query_strings = [];
        $count = 0;
        foreach ($to_run as $file ) {
            $file_full = $migrations_folder . $file;
            if( strpos($file, '.sql', -0) && is_file($file_full) ) {
                $f = fopen($file_full, 'r');
                $line = fgets($f);

                if($steps > $count) {
                    $count++;
                    $query = '';
                    $look = $stop = '';
                    while($line = fgets($f)) {
                        if ( isset($line) && !empty($matches_goes) ) {
                            preg_match($match_stop, $line, $matches_stop);
                            if( !empty($matches_stop) ) {
                                break 1;
                            }

                            $query .= $line;
                        }

                        if(empty($matches_goes)) {
                            preg_match($match_go, $line, $matches_goes);
                        }
                    }
                    $look = $stop = '';
                    $query_strings[$file] = $query;
                }
                fclose($f);
            }
        }

        if(empty($query_strings)) {
            if( $type == 'up') {
                $this->warning('No new migrations to run');
            } else {
                $this->warning('No migrations to rollback');
            }
        }

        foreach ($query_strings as $file => $query) {
            $errors = $this->runQueryString($query);

            if(!empty($errors)) {
                $this->error('Migration Failed!');
                break 1;
            }
            $time = time();

            if( $type == 'up') {
                $template = __DIR__ . '/../../../templates/MigrationRun.txt';
                $file_obj = new File( $template );
                $new = $file_obj->copyTemplateFile( $migrations_run_folder . $file, ['{{time}}'], [$time] );
            } elseif(file_exists($migrations_run_folder . $file)) {
                unlink($migrations_run_folder . $file);
            }

            $this->success('Migration Finished at ' . $time);
        }
    }
}