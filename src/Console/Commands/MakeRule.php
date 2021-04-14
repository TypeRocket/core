<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Str;

class MakeRule extends Command
{
    protected $command = [
        'make:rule',
        'Make new validation rule',
        'This command makes new validation rules.',
    ];

    protected function config()
    {
        $this->addArgument('key', self::REQUIRED, 'The rule key.');
        $this->addArgument('class', self::REQUIRED, 'The rule class name.');
    }

    /**
     * Execute Command
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $ruleClass = $this->getClassArgument('class');
        $ruleKey = $this->getClassArgument('key');

        [$namespace, $class] = Str::splitAt('\\', $ruleClass, true);
        $namespace = implode('\\',array_filter([$this->getGalaxyMakeNamespace(), 'Rules', $namespace]));

        $tags = ['{{namespace}}', '{{class}}', '{{key}}'];
        $replacements = [ $namespace, $class, $ruleKey ];
        $template = __DIR__ . '/../../../templates/Rule.txt';

        $app_path = \TypeRocket\Core\Config::get('paths.app');
        $rule_file = $app_path . '/Rules/' . str_replace("\\",'/', $ruleClass) . ".php";
        $rule_folder = substr($rule_file, 0, -1 + -strlen(basename($rule_file)) ) ;

        if( ! file_exists( $rule_folder ) ) {
            mkdir($rule_folder, 0755, true);
        }

        $rule_file = File::new($template)->copyTemplateFile( $rule_file, $tags, $replacements );

        if( $rule_file ) {
            $this->success('Rule created: ' . $ruleClass );
        } else {
            $this->error('TypeRocket ' . $ruleClass . ' already exists.');
        }
    }
}