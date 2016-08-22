<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
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
        $this->addArgument('type', self::REQUIRED, 'The type: base, post or term.');
        $this->addArgument('name', self::REQUIRED, 'The name of the resource for the controller.');
        $this->addArgument('model', self::OPTIONAL, 'The model for the post or term controller.');
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
        $type = $this->getArgument('type');
        $name = $this->getArgument('name');
        $model = $this->getArgument('model');

        switch ( strtolower($type) ) {
            case 'base' :
            case 'post' :
            case 'term' :
                $type = ucfirst($type);
                break;
            default :
                $this->error('Type must be: base, post or term');
                die();
                break;
        }

        $controller = ucfirst($name) . 'Controller';
        $this->makeFile($controller, $type, $model );
    }

    /**
     * Make file
     *
     * @param $controller
     * @param $type
     * @param $model
     */
    private function makeFile( $controller, $type, $model ) {

        $tags = ['{{namespace}}', '{{controller}}', '{{model}}'];
        $replacements = [ TR_APP_NAMESPACE, $controller, $model ];
        $template = __DIR__ . '/../../../templates/Controllers/' . $type . '.txt';
        $new = TR_PATH . '/app/Controllers/' . $controller . ".php";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success('Controller created: ' . $controller . ' as ' . $type );
        } else {
            $this->error('TypeRocket ' . $controller . ' exists.');
        }

    }

}