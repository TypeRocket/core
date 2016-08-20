<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use TypeRocket\Utility\File;

class MakeController extends Command
{

    protected function configure()
    {
        $this->setName('make:controller')
            ->setDescription('Make new controller')
            ->setHelp("This command allows you to make new controllers.");

        $this->addArgument('type', InputArgument::REQUIRED, 'The type: base, post or term.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the resource for the controller.');
        $this->addArgument('model', InputArgument::OPTIONAL, 'The model for the post or term controller.');
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

        $tags = ['{{namespace}}', '{{controller}}', '{{model}}'];
        $replacements = [ TR_APP_NAMESPACE, $controller, $model ];
        $template = __DIR__ . '/../../../templates/Controllers/' . $type . '.txt';
        $new = TR_PATH . '/app/Controllers/' . $controller . ".php";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $output->writeln('<fg=green>Controller created: ' . $controller . ' as ' . $type . '</>');
        } else {
            $output->writeln('<fg=red>TypeRocket ' . $controller . ' exists.</>');
        }

    }

}