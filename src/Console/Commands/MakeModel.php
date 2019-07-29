<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use TypeRocket\Console\Command;
use TypeRocket\Core\Config;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Inflect;

class MakeModel extends Command
{
    protected $command = [
        'make:model',
        'Make new model',
        'This command allows you to make new models.',
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
     */
    protected function exec()
    {
        $directive = $this->getArgument('directive');
        $name = $this->getArgument('name');
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
                $id = strtolower(Inflect::pluralize($name));
            } else {
                $id = strtolower($name);
            }
        }

        $model = ucfirst($name);
        $this->makeFile($model, $directive, $id);
    }

    /**
     * Make file
     *
     * @param string $model
     * @param string $directive
     * @param string $id
     */
    private function makeFile( $model, $directive, $id ) {

        $tags = ['{{namespace}}', '{{model}}', '{{id}}'];
        $replacements = [ TR_APP_NAMESPACE, $model, $id ];
        $template =  __DIR__ . '/../../../templates/Models/' . $directive . '.txt';
        $app_path = Config::locate('paths.app');
        $new = $app_path . '/Models/' . $model . ".php";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success('Model created: ' . $model . ' as ' . $directive . '</>');
        } else {
            $this->error('TypeRocket ' . $model . ' exists.');
        }

        if ( $this->getOption('controller') ) {
            $command = $this->getApplication()->find('make:controller');
            $input = new ArrayInput( [
                'directive' => $this->getArgument('directive'),
                'name' => $this->getArgument('name')
            ] );
            $command->run($input, $this->output);
        }

    }

}