<?php


namespace TypeRocket\Console\Commands;


use TypeRocket\Console\Command;
use TypeRocket\Core\Config;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Str;

class ClearCache extends Command
{
    protected $command = [
        'cache:clear',
        'Clear Cache',
        'This command clears a cache of your choice.'
    ];

    protected function config()
    {
        $this->addArgument('folder', self::REQUIRED, 'The cache folder to delete all files from.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:controller base member
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $folder = Sanitize::underscore($this->getArgument('folder'));

        $cache_path = Config::locate('paths.cache') ?? TR_PATH . '/storage/cache';

        if(!$cache_path) {
            $this->error('Error no cache path found. Define in paths.php as "cache" using TR_PATH as root.');
            die();
        }

        $glob = glob( $cache_path . "/{$folder}/*");

        foreach ($glob as $value) {
            if( Str::starts( TR_PATH, $value) && file_exists( $value ) ) {
                ( new File($value) )->removeRecursiveDirectory();
                $this->warning('Deleted ' . $value);
            } else {
                $this->error('Error deleting none project file ' . $value);
            }
        }

        $this->success(sprintf('Cache %s cleared', $folder));
    }
}