<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;

class RootInstall extends Command
{
    protected $configSampleWP;
    protected $configWP;
    protected $archiveWP;
    protected $contentWP;

    protected $command = [
        'root:install',
        'Install TypeRocket as root',
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
     * Example command: php galaxy root:install
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function exec()
    {
        // Check for WordPress folder
        if( ! file_exists( TYPEROCKET_PATH . '/wordpress' ) ) {
            $this->error('WordPress folder missing or moved');
            die();
        }

        // Define file paths
        $this->configSampleWP = TYPEROCKET_PATH . '/wordpress/wp-config-sample.php';
        $this->configWP = TYPEROCKET_PATH . '/wp-config.php';

        if(!defined('TYPEROCKET_ROOT_INSTALL'))
            define('TYPEROCKET_ROOT_INSTALL', true);

        // Fail if already installed
        if( file_exists( $this->configSampleWP ) ) {
            $this->error('WordPress may already be installed. Remove all old WordPress files to run root install.');
            die();
        }

        // Run
        $this->runCommand('wp:download');
        $this->configWordPress();
        $this->runCommand('config:seed');

        $this->success('TypeRocket is connected, Happy coding!');
    }

    /**
     * Configure WordPress
     *
     * @return bool
     * @throws \Exception
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
        $needle = '/require_once.*wp-settings.php.\s*?\)?\;/m';
        $replace  = "require __DIR__ . '/init.php'; // Init TypeRocket" . PHP_EOL;
        $replace .= "require_once( ABSPATH . 'wp-settings.php' );";

        if( ! $file->replaceOnLine($needle, $replace, true) ) {
            $this->error('The TypeRocket init.php file was not included in wp-config.php');
        }

        // WP config
        $file->replaceOnLine('database_name_here', $this->getArgument('database'));
        $file->replaceOnLine('username_here', $this->getArgument('username'));
        $file->replaceOnLine('password_here', $this->getArgument('password'));

        // Salts
        $lines = (array) file('https://api.wordpress.org/secret-key/1.1/salt/');
        $regex = "/define(.*)here\'\s?\)\;/m";
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
}
