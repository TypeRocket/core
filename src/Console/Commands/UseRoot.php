<?php

namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeRocket\Utility\File;
use Symfony\Component\Console\Input\ArrayInput;

class UseRoot extends Command
{
    protected $configSampleWP;
    protected $configWP;
    protected $archiveWP;
    protected $contentWP;


    protected function configure()
    {
        $this->setName('use:root')
             ->setDescription('Use TypeRocket as root')
             ->setHelp("This command download WordPress and root TypeRocket.");

        $this->addArgument('database', InputArgument::REQUIRED, 'The database name');
        $this->addArgument('username', InputArgument::REQUIRED, 'The database username');
        $this->addArgument('password', InputArgument::REQUIRED, 'The user passowrd');
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
        // Check for WordPress folder
        if( ! file_exists( TR_PATH . '/wordpress' ) ) {
            $output->writeln('<fg=red>WordPress folder missing or moved');
            die();
        }

        // Define file paths
        $this->configSampleWP = TR_PATH . '/wordpress/wp-config-sample.php';
        $this->configWP = TR_PATH . '/wp-config.php';
        $this->archiveWP = TR_PATH . '/wp.zip';
        $this->contentWP = TR_PATH . '/wordpress/content';

        // Fail if already installed
        if( file_exists( $this->configSampleWP ) ) {
            $output->writeln('<fg=red>WordPress already installed');
            die();
        }

        // Run
        $this->downloadWordPress($input, $output );
        $this->unArchiveWordPress($input, $output );
        $this->configWordPress( $input, $output );
        $this->useTemplates($input, $output);
        $this->updateTypeRocketPaths($input, $output);

        $output->writeln('<fg=green>TypeRocket is connected, Happy coding!');
    }

    /**
     * Configure WordPress
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function configWordPress( InputInterface $input, OutputInterface $output ) {
        // Message
        $output->writeln('<fg=green>Creating wp-config.php');

        // Copy files
        copy( $this->configSampleWP , $this->configWP );
        $file = new File($this->configWP);

        // Add init.php
        $needle = 'require_once(ABSPATH . \'wp-settings.php\');';
        $replace  = "require __DIR__ . '/init.php'; // Init TypeRocket" . PHP_EOL;
        $replace .= "require_once(ABSPATH . 'wp-settings.php');";
        $file->replaceOnLine($needle, $replace);

        // WP config
        $file->replaceOnLine('database_name_here', $input->getArgument('database'));
        $file->replaceOnLine('username_here', $input->getArgument('username'));
        $file->replaceOnLine('password_here', $input->getArgument('password'));

        // Salts
        $lines = (array) file('https://api.wordpress.org/secret-key/1.1/salt/');
        $regex = "/define(.*)here\\'\\)\\;/m";
        preg_match_all($regex, file_get_contents( $file->file ) , $matches);

        if( !empty($lines) && count( $lines ) == count($matches[0]) ) {
            foreach ($lines as $index => $line ) {
                $file->replaceOnLine($matches[0][$index], $line );
            }
        } else {
            // Error
            $output->writeln('<fg=red>WordPress salts failed');
        }

    }

    /**
     * Download WordPress
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function downloadWordPress(InputInterface $input, OutputInterface $output)
    {
        // Message
        $output->writeln('<fg=green>Downloading WordPress');

        // Download
        $file = new File( $this->archiveWP );
        $file->download('https://wordpress.org/latest.zip');
    }

    /**
     * Un-archive WordPress
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function unArchiveWordPress( InputInterface $input, OutputInterface $output ) {
        $zip = new \ZipArchive;

        if ( $zip->open( $this->archiveWP ) ) {
            // Message
            $output->writeln('<fg=green>Extracting WordPress');

            $zip->extractTo( TR_PATH );
            $zip->close();
        } else {
            // Error
            $output->writeln('<fg=red>Error opening archive file');
            die();
        }

        // Cleanup zip file
        if( file_exists( $this->archiveWP ) ) {
            $output->writeln('<fg=green>Archive file deleted');
            unlink( $this->archiveWP );
        }
    }

    /**
     * Use Templates
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function useTemplates(InputInterface $input, OutputInterface $output) {
        $command = $this->getApplication()->find('use:templates');
        $input = new ArrayInput( [ 'path' => TR_PATH . '/wordpress/wp-content' ] );
        $command->run($input, $output);
    }

    /**
     * Update TypeRocket Paths
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function updateTypeRocketPaths(InputInterface $input, OutputInterface $output) {
        // Message
        $output->writeln('<fg=green>Updating Typerocket paths');

        // Update file
        $file = new File(TR_PATH . '/config/paths.php');
        $paths = [
            "'assets' => get_template_directory_uri() . '/typerocket/wordpress/assets'",
            "'components' => get_template_directory_uri() . '/typerocket/wordpress/assets/components'"
        ];

        $replacements = [
            "'assets' => home_url() . '/assets'",
            "'components' => home_url() . '/assets/components'"
        ];

        foreach ($paths as $index => $path) {
            $file->replaceOnLine($path, $replacements[$index]);
        }

    }
}