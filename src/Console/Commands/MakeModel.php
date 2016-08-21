<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Inflect;

class MakeModel extends Command
{

    protected function configure()
    {
        $this->setName('make:model')
             ->setDescription('Make new model')
             ->setHelp("This command allows you to make new models.");

        $this->addArgument('type', InputArgument::REQUIRED, 'The type: base, post or term.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the model.');
        $this->addArgument('id', InputArgument::OPTIONAL, 'The post, base or term WP ID. eg. post, page, category, post_tag...');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:model schema members members
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        $name = $input->getArgument('name');
        $id = $input->getArgument('id');

        switch ( strtolower($type) ) {
            case 'base' :
            case 'post' :
            case 'term' :
                $type = ucfirst($type);
                break;
            default :
                $output->writeln('<fg=red>Type must be: base, post or term</>');
                die();
                break;
        }

        $model = ucfirst($name);
        $this->makeFile($model, $type, $id, $output);
    }

    /**
     * Make file
     *
     * @param $model
     * @param $type
     * @param $id
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function makeFile( $model, $type, $id, OutputInterface $output ) {

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
            $output->writeln('<fg=green>Model created: ' . $model . ' as ' . $type . '</>');
        } else {
            $output->writeln('<fg=red>TypeRocket ' . $model . ' exists.</>');
        }

    }

}