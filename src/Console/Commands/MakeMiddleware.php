<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Str;

class MakeMiddleware extends Command
{

    protected $command = [
        'make:middleware',
        'Make new middleware',
        'This command makes new middleware.',
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
        $middleware = $this->getClassArgument('name');
        $this->makeFile($middleware);
    }

    /**
     * Make file
     *
     * @param string $middleware
     */
    protected function makeFile( $middleware )
    {
        [$namespace, $class] = Str::splitAt('\\', $middleware, true);
        $tags = ['{{namespace}}', '{{middleware}}'];
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Http\Middleware', $namespace]));
        $replacements = [ $namespace, $class ];

        $template = __DIR__ . '/../../../templates/Middleware.txt';
        $app_path = \TypeRocket\Core\Config::get('paths.app');

        $middleware_file = $app_path . '/Http/Middleware/' . str_replace("\\",'/', $middleware) . ".php";
        $middleware_path = substr($middleware_file, 0, -1 + -strlen(basename($middleware_file)) ) ;

        if( ! file_exists( $middleware_path ) ) {
            mkdir($middleware_path, 0755, true);
        }

        $file = new File( $template );
        $middleware_file = $file->copyTemplateFile( $middleware_file, $tags, $replacements );

        if( $middleware_file ) {
            $this->success('Controller created: ' . $middleware );
        } else {
            $this->error('TypeRocket ' . $middleware . ' already exists.');
        }

    }

}