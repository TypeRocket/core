<?php

namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use Symfony\Component\Console\Input\ArrayInput;
use TypeRocket\Utility\Str;

class UseRoot extends Command
{
    protected $configSampleWP;
    protected $configWP;
    protected $archiveWP;
    protected $contentWP;

    protected $command = [
        'use:root',
        'Use TypeRocket as root',
        'This command downloads WordPress and roots TypeRocket.',
    ];

    protected function config()
    {
        $this->addArgument('database', self::REQUIRED, 'The database name');
        $this->addArgument('username', self::REQUIRED, 'The database username');
        $this->addArgument('password', self::REQUIRED, 'The database user password');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy use:root
     *
     * @return int|null|void
     */
    protected function exec()
    {
        // Check for WordPress folder
        if( ! file_exists( TR_PATH . '/wordpress' ) ) {
            $this->error('WordPress folder missing or moved');
            die();
        }

        // Define file paths
        $this->configSampleWP = TR_PATH . '/wordpress/wp-config-sample.php';
        $this->configWP = TR_PATH . '/wp-config.php';
        $this->archiveWP = TR_PATH . '/wp.zip';
        $this->contentWP = TR_PATH . '/wordpress/content';

        // Fail if already installed
        if( file_exists( $this->configSampleWP ) ) {
            $this->error('WordPress already installed');
            die();
        }

        // Run
        $this->downloadWordPress();
        $this->unArchiveWordPress();
        $this->configWordPress();
        $this->useTemplates();
        $this->updateTypeRocketPaths();
        $this->cleanWordPressThemes();

        $this->success('TypeRocket is connected, Happy coding!');
    }

    /**
     * Configure WordPress
     *
     * @return bool
     */
    protected function configWordPress() {
        // Check for wp-config.php
        if( file_exists($this->configWP) ) {
            $this->error('wp-config.php already exists in TypeRocket');
            return false;
        }

        // Message
        $this->success('Creating wp-config.php');

        // Copy files
        copy( $this->configSampleWP , $this->configWP );
        $file = new File($this->configWP);

        // Add init.php
        $needle = 'require_once(ABSPATH . \'wp-settings.php\');';
        $replace  = "require __DIR__ . '/init.php'; // Init TypeRocket" . PHP_EOL;
        $replace .= "require_once(ABSPATH . 'wp-settings.php');";
        $file->replaceOnLine($needle, $replace);

        // WP config
        $file->replaceOnLine('database_name_here', $this->getArgument('database'));
        $file->replaceOnLine('username_here', $this->getArgument('username'));
        $file->replaceOnLine('password_here', $this->getArgument('password'));

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
            $this->error('WordPress salts failed');
        }

        return true;
    }

    /**
     * Download WordPress
     */
    protected function downloadWordPress()
    {
        // Message
        $this->success('Downloading WordPress');

        // Download
        $file = new File( $this->archiveWP );
        $file->download('https://wordpress.org/latest.zip');
    }

    /**
     * Un-archive WordPress
     */
    protected function unArchiveWordPress() {
        $zip = new \ZipArchive;

        if ( $zip->open( $this->archiveWP ) ) {
            // Message
            $this->success('Extracting WordPress');

            $zip->extractTo( TR_PATH );
            $zip->close();
        } else {
            // Error
            $this->error('Error opening archive file');
            die();
        }

        // Cleanup zip file
        if( file_exists( $this->archiveWP ) ) {
            $this->success('Archive file deleted');
            unlink( $this->archiveWP );
        }
    }

    /**
     * Use Templates
     */
    protected function useTemplates() {
        $command = $this->getApplication()->find('use:templates');
        $input = new ArrayInput( [ 'path' => TR_PATH . '/wordpress/wp-content' ] );
        $command->run($input, $this->output);
    }

    /**
     * Update TypeRocket Paths
     */
    protected function updateTypeRocketPaths() {
        // Message
        $this->success('Updating TypeRocket paths');

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

    /**
     * Clean WordPress Themes
     */
    protected function cleanWordPressThemes() {
        $twentyThemes = glob( TR_PATH . "/wordpress/wp-content/themes/twenty*/");

        foreach ($twentyThemes as $value) {

            // Message
            $this->success('Deleting ' . $value);
            if( Str::starts( TR_PATH, $value) && file_exists( $value ) ) {

                // Delete theme
                ( new File($value) )->removeRecursiveDirectory();
            } else {
                $this->error('Error deleting none project file ' . $value);
            }
        }
    }
}