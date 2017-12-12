<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;

class UseTemplates extends Command
{

    protected $command = [
        'use:templates',
        'Use TypeRocket for templates',
        'This command generates mu-plugins to enable using TypeRocket templates.'
    ];

    protected function config()
    {
        $this->addArgument('path', self::REQUIRED, 'The absolute path of wp-content');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy use:templates {wp-content}
     *
     * @return void
     */
    protected function exec()
    {
        $path = rtrim( $this->getArgument('path'), '/');

        if( file_exists($path) ) {
            $template = __DIR__ . '/../../../templates/MU.txt';
            $new = "{$path}/mu-plugins/typerocket.php";

            if( ! file_exists($path . '/mu-plugins') ) {
                mkdir($path . '/mu-plugins', 0755, true);
            }

            $file = new File( $template );
            $created = $file->copyTemplateFile($new);

            if( $created ) {
                $this->success('TypeRocket templates enabled at: ' . $created );
            } else {
                $this->error('TypeRocket templates already enabled ' . $new . ' exists.');
            }

            try {
                $file = new File(TR_PATH . '/config/app.php');
                $enabled = "'use_root' => true,";
                $found = $file->replaceOnLine("'use_root' => false,", $enabled );

                if($found) {
                    $this->success('Enabled templates in config/app.php' );
                } else {
                    $this->error('Manually set use_root in config/app.php to: true');
                }

            } catch ( \Exception $e ) {
                $this->error('File empty or missing');
            }

        } else {
            $this->error('Path not found: ' . $path);
        }
    }

}