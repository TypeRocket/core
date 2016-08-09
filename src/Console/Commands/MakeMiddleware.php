<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use TypeRocket\Utility\File;

class MakeMiddleware extends Command
{

    protected function configure()
    {
        $this->setName('make:middleware')
             ->setDescription('Make new middleware')
             ->setHelp("This command allows you to make new middleware.");

        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the middleware using same letter case.');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy make:middleware MiddlewareName
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

        $tags = ['{{namespace}}', '{{middleware}}'];
        $replacements = [ TR_APP_NAMESPACE, $middleware ];
        $template = __DIR__ . '/../../../templates/Middleware.txt';
        $new = TR_PATH . '/app/Http/Middleware/' . $middleware . ".php";

        $file = new File( $template );
        $new = $file->copyTemplateFile( $new, $tags, $replacements );

        if( $new ) {
            $output->writeln('<fg=green>Controller created: ' . $middleware . '</>');
        } else {
            $output->writeln('<fg=red>TypeRocket ' . $middleware . ' exists.</>');
        }

    }

}