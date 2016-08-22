<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
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
        $this->addArgument('type', self::REQUIRED, 'The type: base, post or term.');
        $this->addArgument('name', self::REQUIRED, 'The name of the model.');
        $this->addArgument('id', self::OPTIONAL, 'The post, base or term WP ID. eg. post, page, category, post_tag...');
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
        $type = $this->getArgument('type');
        $name = $this->getArgument('name');
        $id = $this->getArgument('id');

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

        $model = ucfirst($name);
        $this->makeFile($model, $type, $id);
    }

    /**
     * Make file
     *
     * @param $model
     * @param $type
     * @param $id
     */
    private function makeFile( $model, $type, $id ) {

        if( ! $id ) {
            if( $type == 'Base') {
                $id = strtolower(Inflect::pluralize($model));
            } else {
                $id = strtolower($model);
            }
        }

        $tags = ['{{namespace}}', '{{model}}', '{{id}}'];
        $replacements = [ TR_APP_NAMESPACE, $model, $id ];
        $template =  __DIR__ . '/../../../templates/Models/' . $type . '.txt';
        $new = TR_PATH . '/app/Models/' . $model . ".php";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success('Model created: ' . $model . ' as ' . $type . '</>');
        } else {
            $this->error('TypeRocket ' . $model . ' exists.');
        }

    }

}