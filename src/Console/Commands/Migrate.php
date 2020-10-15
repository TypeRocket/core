<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use TypeRocket\Console\CanQueryDB;
use TypeRocket\Console\Command;

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
        $this->addArgument('type', self::REQUIRED, 'The type of migration to run (up|down|reload|flush).');
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
        $reload = false;

        if(!in_array($type, ['down','up','flush','reload'])) {
            $this->error('Migration type invalid. Use: up, down, reload, or flush.');
            return;
        }

        if(!$steps && $type == 'up') {
            $steps = 99999999999999;
        }

        if(!$steps) {
            $steps = 1;
        }

        if($type == 'flush') {
            $type = 'down';
            $steps = 99999999999999;
        }

        if($type == 'reload') {
            $type = 'down';
            $reload = true;
            $steps = 99999999999999;
        }

        $this->sqlMigrationDirectory($type, $steps, $reload);
    }

    protected function sqlMigrationDirectory($type, $steps = 1, $reload = false) {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $migrations_folder = \TypeRocket\Core\Config::get('paths.migrations');

        if(!file_exists($migrations_folder)) {
            $this->error('Migration folder does not exist: ' . $migrations_folder);
            return;
        }

        $migrations = array_diff(scandir($migrations_folder), ['..', '.'] );
        $migrations = array_flip($migrations);

        $migrations_run = maybe_unserialize(get_option('typerocket_migrations')) ?: [];

        if($type == 'up') {
            $to_run = array_diff_key($migrations, $migrations_run);
            $match_go = '/--\s+\>\>\>\s+Up\s+\>\>\>/';
            $match_stop = '/--\s+\>\>\>\s+Down\s+\>\>\>/';
        } else {
            $to_run = array_reverse($migrations_run);
            $match_go = '/--\s+\>\>\>\s+Down\s+\>\>\>/';
            $match_stop = '/--\s+\>\>\>\s+Up\s+\>\>\>/';
        }


        $query_strings = [];
        $count = 0;
        foreach ($to_run as $file => $index ) {
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

            $time = microtime(true);
            $dtime =  \DateTime::createFromFormat('U.u', $time)->format('Y-m-d\TH:i:s.u');
            usleep(200);

            if( $type == 'up') {
                $migrations_run[$file] = $time;
                $this->success('Migration up finished at ' . $dtime);
            } else {
                unset($migrations_run[$file]);
                $this->warning('Migration down finished at ' . $dtime);
            }

        }

        update_option('typerocket_migrations', $migrations_run);

        if($reload) {
            $command = $this->getApplication()->find('migrate');
            $input = new ArrayInput( [
                'type' => 'up',
            ] );
            $command->run($input, $this->output);
        }
    }
}