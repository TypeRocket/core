<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Core\Config;
use TypeRocket\Utility\File;

class MakeMiddleware extends Command
{

    protected $command = [
        'make:middleware',
        'Make new middleware',
        'This command allows you to make new middleware.',
    ];

    protected function config()
    {
        $this->addArgument('name', self::REQUIRED, 'The name of the middleware using same letter case.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:middleware MiddlewareName
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $middleware = $this->getArgument('name');
        $this->makeFile($middleware);
    }

    /**
     * Make file
     *
     * @param string $middleware
     */
    private function makeFile( $middleware ) {

        $tags = ['{{namespace}}', '{{middleware}}'];
        $replacements = [ TR_APP_NAMESPACE, $middleware ];
        $template = __DIR__ . '/../../../templates/Middleware.txt';
        $app_path = Config::locate('paths.app');
        $new = $app_path . '/Http/Middleware/' . $middleware . ".php";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success('Controller created: ' . $middleware );
        } else {
            $this->error('TypeRocket ' . $middleware . ' exists.');
        }

    }

}