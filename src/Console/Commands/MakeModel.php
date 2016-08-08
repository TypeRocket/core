<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeModel extends Command
{

    protected function configure()
    {
        $this->setName('make:model')
             ->setDescription('Make new model.')
             ->setHelp("This command allows you to make new models.");

        $this->addArgument('type', InputArgument::REQUIRED, 'The type: schema, posttype or taxonomy.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the model.');
        $this->addArgument('id', InputArgument::OPTIONAL, 'The posttype or taxonomy WP ID. eg. post, page, category, post_tag...');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:controller resource members
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
            case 'schema' :
                $type = 'Schema';
                break;
            case 'posttype' :
                $type = 'PostType';
                break;
            case 'taxonomy' :
                $type = 'Taxonomy';
                break;
            default :
                $output->writeln('<fg=red>Type must be: schema, posttype or taxonomy</>');
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

        $replace = ['{{namespace}}', '{{model}}', '{{id}}'];
        $with = [ TR_APP_NAMESPACE, $model, $id ];
        $template = file_get_contents( __DIR__ . '/../../../templates/Models/' . $type . '.txt');

        $modelContent = str_replace($replace, $with, $template);
        $new_file_location = TR_PATH . '/app/Models/' . $model . ".php";

        if( ! file_exists($new_file_location) ) {
            $myfile = fopen( $new_file_location, "w") or die("Unable to open file!");
            fwrite($myfile, $modelContent);
            fclose($myfile);
            $output->writeln('<fg=green>Model created: ' . $model . ' as ' . $type . '</>');
        } else {
            $output->writeln('<fg=red>TypeRocket ' . $model . ' exists.</>');
        }

    }

}