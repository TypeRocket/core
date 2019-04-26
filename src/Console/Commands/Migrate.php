<?php

namespace TypeRocket\Console\Commands;

use TypeRocket\Console\CanQueryDB;
use TypeRocket\Console\Command;
use TypeRocket\Core\Config;
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
        $this->addArgument('type', self::REQUIRED, 'The type of migration to run (up|down|reset).');
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

        if($type == 'reset') {
            $type = 'down';
            $steps = 99999999999999;
        }

        $this->sqlMigrationDirectory($type, $steps);
    }

    protected function sqlMigrationDirectory($type, $steps = 1) {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $root = Config::locate('paths.migrate');
        $migrations_list = is_array($root['migrations']) ? $root['migrations'] : [$root['migrations']];
        $migrations_run_folder = $root['run'];
        $migrations = [];

        foreach ($migrations_list as $migrations_folder) {
            if( ! file_exists( $migrations_folder ) ) {
                $this->error('Migrations folder not found: ' . $migrations_folder);
                return;
            }
            $new_migrations = array_diff(scandir($migrations_folder), ['..', '.'] );
            $migrations = array_merge($migrations, $new_migrations);
        }

        // Make directories if needed
        if( ! file_exists( $migrations_run_folder ) ) {
            $this->warning('TypeRocket trying to locate ' . $root . ' for migrations to run.');
            mkdir($migrations_run_folder, 0755, true);
            $this->success('Location created...');
        }

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
            $file_full = $migrations_folder . '/' . $file;
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
            $run_file = $migrations_run_folder . '/' . $file;

            if( $type == 'up') {
                $template = __DIR__ . '/../../../templates/MigrationRun.txt';
                $file_obj = new File( $template );
                $new = $file_obj->copyTemplateFile($run_file, ['{{time}}'], [$time] );
            } elseif(file_exists($run_file)) {
                unlink($run_file);
            }

            $this->success('Migration Finished at ' . $time);
        }
    }
}