<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
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

        $cache_path = \TypeRocket\Core\Config::get('paths.cache');
        if(!file_exists($cache_path)) {
            mkdir($cache_path, 0755, true);
        }

        if(!Str::starts( TYPEROCKET_ALT_PATH, $cache_path)) {
            $this->error('Error cache path found must be define in paths.php as "cache" using TYPEROCKET_ALT_PATH as root.');
        }

        $location = $cache_path . "/{$folder}/*";
        $glob = glob($location);

        if(empty($glob)) {
            $this->warning('No files found in: ' . $location);
        }

        foreach ($glob as $value) {
            if( Str::starts( TYPEROCKET_ALT_PATH, $value) && file_exists( $value ) ) {
                ( new File($value) )->removeRecursiveDirectory();
                $this->warning('Deleted ' . $value);
            } else {
                $this->error('Error deleting none project file ' . $value);
            }
        }

        $this->success(sprintf('Cache %s cleared', $folder));
    }
}