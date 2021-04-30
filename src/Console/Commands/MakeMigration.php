<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Str;
use function Webmozart\Assert\Tests\StaticAnalysis\upper;

class MakeMigration extends Command
{
    protected $command = [
        'make:migration',
        'Make new migration',
        'This command makes new SQL migrations.',
    ];

    protected function config()
    {
        $this->addArgument('name', self::REQUIRED, 'The migration name.');
        $this->addArgument('type', self::OPTIONAL, 'class or sql');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:migration name_of_migration
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $name = Sanitize::underscore( $this->getArgument('name') );
        $type = strtoupper($this->getArgument('type', 'sql'));
        $root = \TypeRocket\Core\Config::get('paths.migrations');

        // Make directories if needed
        if( ! file_exists($root) ) {
            $this->warning('TypeRocket trying to locate ' . $root . ' for migrations.');
            mkdir($root, 0755, true);
            $this->success('Location created...');
        }

        $tags = ['{{name}}'];
        $replacements = [ $name ];

        if($type === 'SQL') {
            $template = __DIR__ . '/../../../templates/Migration.txt';
            $ext = ".sql";
        } else {
            $template = __DIR__ . '/../../../templates/MigrationClass.txt';
            $ext = ".php";
        }

        // Make migration file
        $fileName = time() . '.' . $name . $ext;
        $new = $root . '/' . $fileName;
        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $this->success($type .' Migration created: ' . $name . ' as ' . $fileName );
        } else {
            $this->error('TypeRocket migration ' . $name . ' already exists.');
        }
    }
}