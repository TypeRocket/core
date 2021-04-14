<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Inflect;
use TypeRocket\Utility\Str;

class MakeModel extends Command
{
    protected $command = [
        'make:model',
        'Make new model',
        'This command makes new models.',
    ];

    protected function config()
    {
        $this->addArgument('directive', self::REQUIRED, 'The type: base, post or term.');
        $this->addArgument('name', self::REQUIRED, 'The name of the model.');
        $this->addArgument('id', self::OPTIONAL, 'The post, base or term WP ID. eg. post, page, category, post_tag...');
        $this->addOption( 'controller', 'c', InputOption::VALUE_NONE, 'Make a controller as well' );
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:model base member
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function exec()
    {
        $directive = $this->getArgument('directive');
        $name = $this->getClassArgument('name');

        $id = $this->getArgument('id');

        switch ( strtolower($directive) ) {
            case 'base' :
            case 'post' :
            case 'term' :
                $directive = ucfirst($directive);
                break;
            default :
                $this->error('Type must be: base, post or term');
                die();
                break;
        }

        if( ! $id ) {
            if( $directive == 'Base') {
                $id = Str::splitAt('\\', strtolower(Inflect::pluralize($name)), true)[1];
            } else {
                $id = strtolower($name);
            }
        }

        $this->makeFile($name, $directive, $id);
    }

    /**
     * Make file
     *
     * @param string $model
     * @param string $directive
     * @param string $id
     *
     * @throws \Exception
     */
    protected function makeFile( $model, $directive, $id )
    {
        [$namespace, $class] = Str::splitAt('\\', $model, true);

        $tags = ['{{namespace}}', '{{model}}', '{{id}}', '{{app}}'];
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Models', $namespace]));
        $replacements = [ $namespace, $class, str_replace('\\', '_', $id), $this->getGalaxyMakeNamespace() ];
        $template =  __DIR__ . '/../../../templates/Models/' . $directive . '.txt';

        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $model_file = $app_path . '/Models/' . str_replace("\\",'/', $model) . ".php";
        $model_path = substr($model_file, 0, -1 + -strlen(basename($model_file)) ) ;

        if( ! file_exists( $model_path ) ) {
            mkdir($model_path, 0755, true);
        }

        $file = new File( $template );
        $model_file = $file->copyTemplateFile( $model_file, $tags, $replacements );

        if( $model_file ) {
            $this->success('Model created: ' . $model . ' as ' . $directive . '</>');
        } else {
            $this->error('TypeRocket ' . $model . ' already exists.');
        }

        if ( $this->getOption('controller') ) {
            $command = $this->getApplication()->find('make:controller');
            $input = new ArrayInput( [
                'directive' => $this->getArgument('directive'),
                'name' => $model . 'Controller'
            ] );
            $command->run($input, $this->output);
        }
    }
}