<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use TypeRocket\Console\Command;
use TypeRocket\Exceptions\MigrationException;
use TypeRocket\Exceptions\SqlException;

class Migrate extends Command
{
    protected $command = [
        'migrate',
        'Run migrations',
        'This command runs migrations.',
    ];

    protected function config()
    {
        $this->addArgument('type', self::REQUIRED, 'The type of migration to run (up|down|reload|flush).');
        $this->addArgument('steps', self::OPTIONAL, 'The limit of migrations to run as int.');
        $this->addOption('path', 'p', self::OPTIONAL, 'Manually define migrations folder path.');
        $this->addOption('wp_option', 'wo', self::OPTIONAL, 'Manually define migrations option name.');
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

        if($type === 'down') {
            $this->error('This action is highly destructive.');
            if(!$this->continue()) {
                return;
            }
        }

        try {
            $path = $this->getOption('path');
            $table = $this->getOption('wp_option');
            $m = new \TypeRocket\Database\Migrate();
            $results = $m->setOption($table)->runMigrationDirectory($type, $steps, $reload, $path, function($report, $result) {
                $this->success($report['message']);
                $this->success($result['message']);
                $this->warning("{$result['type']}:" );
                $this->line($report['wpdb']);
            });
        } catch (\Exception $e) {

            if(typerocket_env('WP_DEBUG', false)) {
                $this->error($e->getFile() . ':' . $e->getLine());
            }

            $this->error($e->getMessage());

            if($e instanceof SqlException) {
                $this->warning('Failed SQL:' );
                $this->line( $e->getSql() );
                $this->error( $e->getSqlError() );
            }

            if($e instanceof MigrationException && $e->errorType === 'info') {
                return;
            }

            exit(1);
        }
    }
}