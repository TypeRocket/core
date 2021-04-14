<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Str;

class MakeController extends Command
{
    protected $command = [
        'make:controller',
        'Make new controller',
        'This command makes new controllers.',
    ];

    protected function config()
    {
        $this->addArgument('directive', self::REQUIRED, 'The directive: base, thin, template, post or term.');
        $this->addArgument('name', self::REQUIRED, 'The name of the resource for the controller.');
        $this->addArgument('model', self::OPTIONAL, 'The model for the post or term controller.');
        $this->addOption( 'model', 'm', InputOption::VALUE_NONE, 'Make a model as well' );
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:controller base member
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function exec()
    {
        $directive = $this->getArgument('directive');
        $name = $this->getClassArgument('name');
        $model = $this->getArgument('model');

        if( ! $model ) {
            if(Str::ends('Controller', $name)) {
                $model = substr($name, 0, -strlen('Controller'));
            } else {
                $model = $name;
                $name = $name . 'Controller';
            }
        }

        switch ( strtolower($directive) ) {
            case 'base' :
            case 'post' :
            case 'thin' :
            case 'template' :
            case 'term' :
                $directive = ucfirst($directive);
                break;
            default :
                $this->error('Type must be: base, thin, template, post or term');
                die();
                break;
        }
        $this->makeFile($name, $directive, $model );
    }

    /**
     * Make file
     *
     * @param string $controller
     * @param string $directive
     * @param string $model
     * @throws \Exception
     */
    protected function makeFile( $controller, $directive, $model )
    {
        [$namespace, $class] = Str::splitAt('\\', $controller, true);
        $mc = Str::splitAt('\\', $model, true)[1];
        $tags = ['{{namespace}}', '{{controller}}', '{{model}}', '{{app}}', '{{mc}}', '{{var}}'];
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Controllers', $namespace]));
        $replacements = [ $namespace, $class, $model, $this->getGalaxyMakeNamespace(), $mc, Str::snake($mc) ];

        $template = __DIR__ . '/../../../templates/Controllers/' . $directive . '.txt';
        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $controller_file = $app_path . '/Controllers/' . str_replace("\\",'/', $controller) . ".php";
        $controller_path = substr($controller_file, 0, -1 + -strlen(basename($controller_file)) ) ;

        if( ! file_exists( $controller_path ) ) {
            mkdir($controller_path, 0755, true);
        }

        $file = new File( $template );
        $controller_file = $file->copyTemplateFile( $controller_file, $tags, $replacements );

        if( $controller_file ) {
            $this->success('Controller created: ' . $controller . ' as ' . $directive );
        } else {
            $this->error('TypeRocket ' . $controller . ' already exists.');
        }

        if ( $this->getOption('model') ) {
            $directive = $this->getArgument('directive');

            if( in_array($directive, ['thin', 'template']) ) {
                $directive = 'base';
            }

            $command = $this->getApplication()->find('make:model');
            $input = new ArrayInput( [
                'directive' => $directive,
                'name' => $model
            ] );
            $command->run($input, $this->output);
        }
    }
}