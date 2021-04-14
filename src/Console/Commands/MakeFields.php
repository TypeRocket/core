<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Str;

class MakeFields extends Command
{
    protected $command = [
        'make:fields',
        'Make new HTTP fields container',
        'This command makes new HTTP fields container.',
    ];

    protected function config()
    {
        $this->addArgument('name', self::REQUIRED, 'The service name.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:fields PostFields
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $name = $this->getClassArgument('name');

        [$namespace, $class] = Str::splitAt('\\', $name, true);
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Http\Fields', $namespace]));
        $replacements = [ $namespace, $class];
        $tags = ['{{namespace}}', '{{fields}}'];

        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $service_file = $app_path . '/Http/Fields/' . str_replace("\\",'/', $name) . ".php";
        $service_path = substr($service_file, 0, -1 + -strlen(basename($service_file)) ) ;

        if( ! file_exists( $service_path ) ) {
            mkdir($service_path, 0755, true);
        }

        $template = __DIR__ . '/../../../templates/Fields.txt';


        $file = new File( $template );
        $service_file = $file->copyTemplateFile( $service_file, $tags, $replacements );

        if( $service_file ) {
            $this->success('Fields created: ' . $name);
        } else {
            $this->error('TypeRocket ' . $name . ' already exists.');
        }

    }
}