<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeController extends Command
{

    protected function configure()
    {
        $this->setName('make:controller')
            ->setDescription('Make new controller')
            ->setHelp("This command allows you to make new controllers.");

        $this->addArgument('type', InputArgument::REQUIRED, 'The type: resource, posttype or taxonomy.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the resource for the controller.');
        $this->addArgument('model', InputArgument::OPTIONAL, 'The model for the posttype or taxonomy controller.');
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
        $model = $input->getArgument('model');

        switch ( strtolower($type) ) {
            case 'resource' :
                $type = 'Resource';
                break;
            case 'posttype' :
                $type = 'PostType';
                break;
            case 'taxonomy' :
                $type = 'Taxonomy';
                break;
            default :
                $output->writeln('<fg=red>Type must be: resource, posttype or taxonomy</>');
                die();
                break;
        }

        $controller = ucfirst($name) . 'Controller';
        $this->makeFile($controller, $type, $model, $output);
    }

    /**
     * Make file
     *
     * @param $controller
     * @param $type
     * @param $model
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function makeFile( $controller, $type, $model, OutputInterface $output ) {

        $replace = ['{{namespace}}', '{{controller}}', '{{model}}'];
        $with = [ TR_APP_NAMESPACE, $controller, $model ];
        $template = file_get_contents( __DIR__ . '/../../../templates/Controllers/' . $type . '.txt');

        $controllerContent = str_replace($replace, $with, $template);
        $new_file_location = TR_PATH . '/app/Controllers/' . $controller . ".php";

        if( ! file_exists($new_file_location) ) {
            $myfile = fopen( $new_file_location, "w") or die("Unable to open file!");
            fwrite($myfile, $controllerContent);
            fclose($myfile);
            $output->writeln('<fg=green>Controller created: ' . $controller . ' as ' . $type . '</>');
        } else {
            $output->writeln('<fg=red>TypeRocket ' . $controller . ' exists.</>');
        }

    }

}