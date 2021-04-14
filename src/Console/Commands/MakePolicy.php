<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Str;

class MakePolicy extends Command
{
    protected $command = [
        'make:policy',
        'Make new auth policy',
        'This command makes new auth policies.',
    ];

    protected function config()
    {
        $this->addArgument('name', self::REQUIRED, 'The auth name.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:policy MyPolicy
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $name = $this->getClassArgument('name');

        [$namespace, $class] = Str::splitAt('\\', $name, true);
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Auth', $namespace]));
        $replacements = [ $namespace, $class, Helper::appNamespace('Models\User') ];
        $tags = ['{{namespace}}', '{{auth}}', '{{user}}'];

        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $policy_file = $app_path . '/Auth/' . str_replace("\\",'/', $name) . ".php";
        $policy_path = substr($policy_file, 0, -1 + -strlen(basename($policy_file)) ) ;

        if( ! file_exists( $policy_path ) ) {
            mkdir($policy_path, 0755, true);
        }

        $template = __DIR__ . '/../../../templates/Auth.txt';

        $file = new File( $template );
        $policy_file = $file->copyTemplateFile( $policy_file, $tags, $replacements );

        if( $policy_file ) {
            $this->success('Auth created: ' . $name );
        } else {
            $this->error('TypeRocket ' . $name . ' already exists.');
        }

    }
}