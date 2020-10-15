<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;

class RootMuPluginInstall extends Command
{

    protected $command = [
        'root:mu',
        'Install root MU plugin',
        'This command generates mu-plugins to enable using TypeRocket as root.'
    ];

    protected function config()
    {
        $this->addArgument('path', self::OPTIONAL, 'The absolute path of wp-content');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy root:mu {wp-content}
     *
     * @return void
     */
    protected function exec()
    {
        $wp_content = rtrim( $this->getArgument('path'), '/');
        $path = $wp_content ?: TYPEROCKET_PATH . '/wordpress/wp-content';

        if( file_exists($path) ) {
            $template = __DIR__ . '/../../../templates/MU.txt';
            $new = "{$path}/mu-plugins/typerocket.php";

            if( ! file_exists($path . '/mu-plugins') ) {
                mkdir($path . '/mu-plugins', 0755, true);
            }

            if(file_exists($new)) {
                $this->warning('Removing old TypeRocket MU Plugin created at: ' . $new );
                unlink($new);
            }

            $file = new File( $template );
            $created = $file->copyTemplateFile($new);

            if( $created ) {
                $this->success('TypeRocket MU Plugin created at: ' . $created );
            } else {
                $this->error('TypeRocket MU Plugin already created ' . $new . ' exists.');
            }

        } else {
            $this->error('Path not found: ' . $path);
        }
    }

}