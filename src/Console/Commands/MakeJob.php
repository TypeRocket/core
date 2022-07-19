<?php

namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Str;

class MakeJob extends Command
{
    protected $command = [
        'make:job',
        'Make new job',
        'This command makes new job for the action scheduler.',
    ];

    protected function config()
    {
        $this->addArgument('class', self::REQUIRED, 'The job class name.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:job MyJobClass
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $className = $this->getClassArgument('class');

        [$namespace, $class] = Str::splitAt('\\', $className, true);
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Jobs', $namespace]));

        $tags = ['{{namespace}}', '{{class}}'];
        $replacements = [ $namespace, $class ];
        $template = __DIR__ . '/../../../templates/Job.txt';

        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $job_file = $app_path . '/Jobs/' . str_replace("\\",'/', $className) . ".php";
        $command_path = substr($job_file, 0, -1 + -strlen(basename($job_file)) ) ;

        if( ! file_exists( $command_path ) ) {
            mkdir($command_path, 0755, true);
        }

        if( ! file_exists( $app_path . '/Jobs' ) ) {
            mkdir($app_path . '/Jobs', 0755, true);
        }

        $file = new File( $template );
        $job_file = $file->copyTemplateFile( $job_file, $tags, $replacements );

        if( $job_file ) {
            $this->success('Job created: ' . $className );

            $file = new File(TYPEROCKET_CORE_CONFIG_PATH . '/queue.php');

            if($file->exists()) {
                $eol = PHP_EOL;
                $new = "        \\$namespace\\$class::class,{$eol}";
                if(!$file->replaceOnLine("'jobs' => [", "'jobs' => [{$eol}{$new}")) {
                    $this->warning('Register your new job ' . $className . ' to config/queue.php' );
                } else {
                    $this->success('Your new job ' . $className . ' was added to config/queue.php' );
                }
            }


        } else {
            $this->error('TypeRocket ' . $className . ' exists.');
        }
    }
}