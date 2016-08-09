<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use TypeRocket\Utility\File;

class UseTemplates extends Command
{

    protected function configure()
    {
        $this->setName('use:templates')
             ->setDescription('Use TypeRocket for templates')
             ->setHelp("This command generates mu-plugins to enable using TypeRocket templates.");

        $this->addArgument('path', InputArgument::REQUIRED, 'The absolute path of wp-content');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy use:templates {wp-content}
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
            $template = __DIR__ . '/../../../templates/MU.txt';
            $new = "{$path}/mu-plugins/typerocket.php";

            if( ! file_exists($path . '/mu-plugins') ) {
                mkdir($path . '/mu-plugins', 0755, true);
            }

            $file = new File( $template );
            $created = $file->copyTemplateFile($new);

            if( $created ) {
                $output->writeln('<fg=green>TypeRocket templates enabled at: ' . $created . '</>');
            } else {
                $output->writeln('<fg=red>TypeRocket templates already enabled ' . $new . ' exists.</>');
            }

            try {
                $file = new File(TR_PATH . '/config/app.php');
                $enabled = "'templates' => 'templates',";
                $found = $file->replaceOnLine("'templates' => false,", $enabled );

                if($found) {
                    $output->writeln('<fg=green>Enabled templates in config/app.php' );
                } else {
                    $output->writeln('<fg=red>Manually set templates in config/app.php to: \'templates\'');
                }

            } catch ( \Exception $e ) {
                $output->writeln('<fg=red>File empty or missing');
            }

        } else {
            $output->writeln('<fg=red>Path not found: ' . $path);
        }
    }

}