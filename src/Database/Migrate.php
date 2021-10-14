<?php
namespace TypeRocket\Database;

use TypeRocket\Core\Config;
use TypeRocket\Exceptions\MigrationException;

class Migrate
{
    public $migrationsFolder = null;
    public $option = null;
    public $callback = null;

    /**
     * Migrate constructor.
     *
     * @param null|string $migrationsFolder
     * @param null|callable $callback
     * @param string $option
     */
    public function __construct($migrationsFolder = null, $callback = null, $option = 'typerocket_migrations')
    {
        $this->setOption($option);
        $this->setFolder($migrationsFolder ?? Config::get('paths.migrations'));
        $this->setCallback($callback);
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     * @throws \Exception
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }

    /**
     * Set the migrartion folder
     *
     * @param null|string $migrationsFolder
     *
     * @return static
     */
    public function setFolder(?string $migrationsFolder)
    {
        if(!is_dir($migrationsFolder)) {
            throw new \Exception('Migration folder does not exist: ' . $migrationsFolder);
        }

        $this->migrationsFolder = $migrationsFolder;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFolder()
    {
        return $this->migrationsFolder;
    }

    /**
     * Set WP Option Name
     *
     * Set wp_options name to save run migration timestamps too
     *
     * @param null|string $option
     *
     * @return static
     */
    public function setOption(?string $option)
    {
        $this->option = $option ?? $this->option;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * Set Function
     *
     * Accesses result run after a simple migration query completes.
     *
     * @param null|callable $callback
     *
     * @return static
     */
    public function setCallback(?callable $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return null|callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Get Migrations Run
     *
     * @return array|mixed|string
     */
    public function getMigrationsRun()
    {
        return maybe_unserialize(get_option($this->option)) ?: [];
    }

    /**
     * Get Migrations From Folder
     *
     * @return array
     */
    public function getMirgationsFromFolder()
    {
        $migrations = array_diff(scandir($this->migrationsFolder), ['..', '.'] );
        return array_flip($migrations);
    }

    /**
     * Get Migrations Not Yet Run
     *
     * @return array
     */
    public function getMigrationsNotRun()
    {
        return array_diff_key($this->getMirgationsFromFolder(), $this->getMigrationsRun());
    }

    /**
     * @param string $type up|down
     * @param int $steps number of migrations to run
     * @param false $reload only use with type 'down' and step 999999999
     * @param null|string $migrationsFolder the folder with the migrartions in it
     * @param null|callable $callback access result run after a simple migration query completes
     *
     * @return array
     * @throws \Exception
     */
    public function runMigrationDirectory(string $type, $steps = 1, $reload = false, $migrationsFolder = null, $callback = null)
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        if($migrationsFolder) {
            $this->setFolder($migrationsFolder);
        }

        if($callback) {
            $this->setCallback($callback);
        }

        $result = [
            'message' => null,
            'success' => true,
            'type' => $type,
            'report' => [],
            'migrations_run' => [],
        ];

        $migrations = $this->getMirgationsFromFolder();
        $migrations_run = $this->getMigrationsRun();

        if($type == 'up') {
            $to_run = array_diff_key($migrations, $migrations_run);
            $match_start = '/--\s+\>\>\>\s+Up\s+\>\>\>/';
            $match_stop = '/--\s+\>\>\>\s+Down\s+\>\>\>/';
        } else {
            $to_run = array_reverse($migrations_run);
            $match_start = '/--\s+\>\>\>\s+Down\s+\>\>\>/';
            $match_stop = '/--\s+\>\>\>\s+End\s+\>\>\>/'; // not required
        }

        $query_strings = [];
        $count = 0;
        $steps = (int) $steps;

        foreach ($to_run as $file => $index ) {
            $file_full = $this->migrationsFolder . '/' . $file;

            if($steps === $count) {
                break 1;
            }

            $count++;

            if( strpos($file, '.sql', -0) && is_file($file_full) ) {
                $f = fopen($file_full, 'r');
                $query = 'seeking...';
                while($line = fgets($f)) {

                    if( preg_match($match_stop, $line) ) {
                        break 1;
                    }

                    $query .= $line;

                    if ( preg_match($match_start, $line) ) {
                        $query = '';
                    }
                }
                $query_strings[$file] = $query;

                fclose($f);
            }

            if(strpos($file, '.php', -0) && is_file($file_full)) {
                $query_strings[$file] = ['file' => $file_full];
            }
        }

        if(empty($query_strings)) {
            if( $type == 'up') {
                $result['message'] = 'No new migrations to run';
            } else {
                $result['message'] = 'No migrations to rollback';
            }

            $migrationError = new MigrationException($result['message']);
            $migrationError->errorType = 'info';
            throw $migrationError;
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

            if(is_array($query) && !empty($query['file'])) {

                $cb = function($type, $file, \wpdb $wpdb) {
                    $migrationObject = include($file);
                    ob_start();
                    $migrationObject->run($type);
                    return ob_get_clean();
                };

                $report = $cb($type, $query['file'], $wpdb);

                if(is_callable($this->callback)) {
                    call_user_func($this->callback, ['message' => 'PHP Migration of ' . $file, 'wpdb' => $report], $result);
                }

                $result['report'] = $file;
            } else {
                $result['report'] = (new SqlRunner())->runQueryString($query, $this->callback, $result, $file);
            }
        }

        $result['migrations_run'] = $migrations_run;

        update_option($this->option, $migrations_run);

        if($reload) {
            $result['reload'] = $this->runMigrationDirectory('up', 99999999999999);
        }

        return $result;
    }
}