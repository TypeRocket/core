<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use TypeRocket\Console\Command;
use TypeRocket\Core\Config;
use TypeRocket\Utility\File;

class MakeController extends Command
{
    protected $command = [
        'make:controller',
        'Make new controller',
        'This command allows you to make new controllers.',
    ];

    protected function config()
    {
        $this->addArgument('directive', self::REQUIRED, 'The directive: base, thin, post or term.');
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
     */
    protected function exec()
    {
        $directive = $this->getArgument('directive');
        $name = $this->getArgument('name');
        $model = $this->getArgument('model');

        if( ! $model ) {
            $model = $name;
        }

        switch ( strtolower($directive) ) {
            case 'base' :
            case 'post' :
            case 'thin' :
            case 'term' :
                $directive = ucfirst($directive);
                break;
            default :
                $this->error('Type must be: base, thin, post or term');
                die();
                break;
        }

        $controller = ucfirst($name) . 'Controller';
        $this->makeFile($controller, $directive, $model );
    }

    /**
     * Make file
     *
     * @param string $controller
     * @param string $directive
     * @param string $model
     */
    private function makeFile( $controller, $directive, $model ) {

        $tags = ['{{namespace}}', '{{controller}}', '{{model}}'];
        $replacements = [ TR_APP_NAMESPACE, $controller, $model ];
        $template = __DIR__ . '/../../../templates/Controllers/' . $directive . '.txt';
        $app_path = Config::locate('paths.app');
        $new = $app_path . '/Controllers/' . $controller . ".php";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success('Controller created: ' . $controller . ' as ' . $directive );
        } else {
            $this->error('TypeRocket ' . $controller . ' exists.');
        }

        if ( $this->getOption('model') ) {
            $directive = $this->getArgument('directive');

            if( $directive == 'thin' ) {
                $directive = 'base';
            }

            $command = $this->getApplication()->find('make:model');
            $input = new ArrayInput( [
                'directive' => $directive,
                'name' => $this->getArgument('name')
            ] );
            $command->run($input, $this->output);
        }

    }

}