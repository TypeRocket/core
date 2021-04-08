<?php
namespace TypeRocket\Database;

use TypeRocket\Core\Config;

class Migrate
{
    public function sqlMigrationDirectory($type, $steps = 1, $reload = false, $migrations_folder = null, $callback = null) {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $migrations_folder = $migrations_folder ?? Config::get('paths.migrations');

        $result = [
            'message' => null,
            'success' => true,
            'type' => $type,
            'report' => [],
            'migrations_run' => [],
        ];

        if(!file_exists($migrations_folder)) {
            throw new \Exception('Migration folder does not exist: ' . $migrations_folder);
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
                $result['message'] = 'No new migrations to run';
            } else {
                $result['message'] = 'No migrations to rollback';
            }

            throw new \Exception($result['message']);
        }

        foreach ($query_strings as $file => $query) {
            $time = microtime(true);
            $dtime = \DateTime::createFromFormat('U.u', $time)->format('Y-m-d\TH:i:s.u');
            usleep(200);

            if( $type == 'up') {
                $migrations_run[$file] = $time;
                $result['message'] = 'Migration up finished at ' . $dtime;
            } else {
                unset($migrations_run[$file]);
                $result['message'] =  'Migration down finished at ' . $dtime;
            }

            $result['report'] = (new SqlRunner())->runQueryString($query, $callback, $result);
        }

        $result['migrations_run'] = $migrations_run;

        update_option('typerocket_migrations', $migrations_run);

        if($reload) {
            $result['reload'] = static::sqlMigrationDirectory('up', 99999999999999);
        }

        return $result;
    }
}