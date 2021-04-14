<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Str;

class MakeComposer extends Command
{
    protected $command = [
        'make:composer',
        'Make new view composer',
        'This command makes new view composers.',
    ];

    protected function config()
    {
        $this->addArgument('class', self::REQUIRED, 'The name of the view composer.');
        $this->addArgument('type', self::OPTIONAL, 'The type of the view composer.');
    }

    /**
     * Execute Command
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function exec()
    {
        $class = $this->getClassArgument('class');
        $type = $this->getArgument('type');
        $this->makeFile($class, $type);
    }

    /**
     * Make file
     *
     * @param string $class
     *
     * @throws \Exception
     */
    protected function makeFile($class, $type)
    {
        [$namespace, $class] = Str::splitAt('\\', $class, true);

        $tags = ['{{namespace}}', '{{class}}'];
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Composers', $namespace]));
        $replacements = [ $namespace, $class ];

        switch ($type) {
            case 'post' :
                $template =  __DIR__ . '/../../../templates/Composers/PostTypeModelComposer.txt';
                break;
            default :
                $template =  __DIR__ . '/../../../templates/Composers/Composer.txt';
                break;
        }

        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $composer_file = $app_path . '/Composers/' . str_replace("\\",'/', $class) . ".php";
        $composer_path = substr($composer_file, 0, -1 + -strlen(basename($composer_file)) ) ;

        if( ! file_exists( $composer_path ) ) {
            mkdir($composer_path, 0755, true);
        }

        $composer_file = File::new($template)->copyTemplateFile( $composer_file, $tags, $replacements );

        if( $composer_file ) {
            $this->success('Composer ' . $class . ' created at ' . $composer_file);
        } else {
            $this->error('Composer ' . $class . ' already exists.');
        }

    }

}