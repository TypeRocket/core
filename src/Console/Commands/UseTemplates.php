<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class UseTemplates extends Command
{

    protected function configure()
    {
        $this->setName('templates')
             ->setDescription('Use TypeRocket for templates')
             ->setHelp("This command generates mu-plugins to enable using TypeRocket templates.");

        $this->addArgument('path', InputArgument::REQUIRED, 'The absolute path of wp-content');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy seed
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = rtrim($input->getArgument('path'), '/');

        if( file_exists($path) ) {
            $template = file_get_contents( __DIR__ . '/../../../templates/MU.txt');
            $new_file_location = "{$path}/mu-plugins/typerocket.php";

            if( ! file_exists($path . '/mu-plugins') ) {
                mkdir($path . '/mu-plugins', 0755, true);
            }

            if( ! file_exists($new_file_location) ) {
                $myfile = fopen( $new_file_location, "w") or die("Unable to open file!");
                fwrite($myfile, $template);
                fclose($myfile);
                $output->writeln('<fg=green>TypeRocket templates enabled at: ' . $new_file_location . '</>');
            } else {
                $output->writeln('<fg=red>TypeRocket templates already enabled ' . $new_file_location . ' exists.</>');
            }
        } else {
            $output->writeln('<fg=red>Path not found: ' . $path);
        }
    }

}