<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeMiddleware extends Command
{

    protected function configure()
    {
        $this->setName('make:middleware')
             ->setDescription('Make new middleware.')
             ->setHelp("This command allows you to make new middleware.");

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the middleware using same letter case.');
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
        $middleware = $input->getArgument('name');
        $this->makeFile($middleware, $output);
    }

    /**
     * Make file
     *
     * @param $middleware
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function makeFile( $middleware, OutputInterface $output ) {

        $replace = ['{{namespace}}', '{{middleware}}'];
        $with = [ TR_APP_NAMESPACE, $middleware ];
        $template = file_get_contents( __DIR__ . '/../../../templates/Middleware.txt');

        $controllerContent = str_replace($replace, $with, $template);
        $new_file_location = TR_PATH . '/app/Http/Middleware/' . $middleware . ".php";

        if( ! file_exists($new_file_location) ) {
            $myfile = fopen( $new_file_location, "w") or die("Unable to open file!");
            fwrite($myfile, $controllerContent);
            fclose($myfile);
            $output->writeln('<fg=green>Controller created: ' . $middleware . '</>');
        } else {
            $output->writeln('<fg=red>TypeRocket ' . $middleware . ' exists.</>');
        }

    }

}