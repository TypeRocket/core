<?php

namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeRocket\Utility\File;
use Symfony\Component\Console\Input\ArrayInput;

class UseRoot extends Command
{
    protected function configure()
    {
        $this->setName('use:root')
             ->setDescription('Use TypeRocket as root')
             ->setHelp("This command download WordPress and root TypeRocket.");
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy use:root
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = TR_PATH . '/wordpress/wp-config-sample.php';
        if( file_exists( $config ) ) {
            $output->writeln('<fg=red>WordPress already installed</>');
            die();
        }

        // download wp
        $file = new File( TR_PATH . '/wp.zip' );
        $output->writeln('<fg=green>Downloading');
        $file->download('https://wordpress.org/latest.zip');
        $output->writeln('<fg=green>Extracting');
        $zip = new \ZipArchive;

        if ($zip->open( $file->file ) === TRUE) {
            $zip->extractTo( TR_PATH );
            $zip->close();

            $output->writeln('<fg=green>Configuring');
            if( file_exists( $file->file ) ) {
                unlink( $file->file );
            }
            $new_config = TR_PATH . '/wp-config.php';
            rename( $config, $new_config);

            // enable templates
            $command = $this->getApplication()->find('use:templates');
            $input = new ArrayInput( [ 'path' => TR_PATH . '/wordpress/wp-content' ] );
            $command->run($input, $output);

            // Include init.php
            $replace = "require __DIR__ . '/init.php'; // Init TypeRocket" . PHP_EOL . "require_once(ABSPATH . 'wp-settings.php');";
            (new File($new_config))->replaceOnLine('require_once(ABSPATH . \'wp-settings.php\');', $replace);

            $output->writeln('<fg=green>TypeRocket is connected, Happy coding!');
        } else {
            $output->writeln('<fg=red>Rooting failed</>');
        }

    }
}