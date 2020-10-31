<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Input\InputOption;
use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Str;

class DownloadWordPress extends Command
{
    protected $archiveWP;
    protected $path;
    protected $type = 'ignore';

    protected $command = [
        'wp:download',
        'Download WordPress',
        'This command downloads WordPress and unzips it.'
    ];

    protected function config()
    {
        $this->archiveWP = TYPEROCKET_PATH . '/wp.zip';
        $this->addArgument('type', self::OPTIONAL, 'Process WordPress themes and plugins: all, core, or cleanup');
        $this->addArgument('path', self::OPTIONAL, 'The absolute path where WP will download');
        $this->addOption('build', 'b', InputOption::VALUE_REQUIRED, 'Download nightly build or specific version of WordPress' );
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy wp:download
     *
     * @return void
     */
    protected function exec()
    {
        $path = $this->getArgument('path');
        $type = $this->getArgument('type');
        $this->type = $type ?: 'all';
        $this->path = rtrim( $path ?  $path : Helper::wordPressRootPath(), '/');

        switch($this->type) {
            case 'all' :
                $this->info('Downloading WordPress core with theme and plugin files.');
                break;
            case 'core' :
                $this->info('Downloading WordPress core without theme and plugin files.');
                break;
            case 'cleanup' :
                $this->warning('Default WordPress themes and plugins will be removed.');
                $this->warning('If you have a theme name starting with twenty* if may have been removed.');
                $this->confirm('Continue with download and cleanup? (y|n) ');
                break;
            default :
                $this->error('Invalid command options include: all, core, or cleanup');
                die();
                break;
        }

        $this->downloadWordPress();
        $this->unArchiveWordPress();
        $this->cleanWordPress();
    }

    /**
     * Download WordPress
     */
    protected function downloadWordPress()
    {
        // Remove old ZIP
        if(file_exists($this->archiveWP)) {
            unlink($this->archiveWP);
        }

        // Download
        $file = new File( $this->archiveWP );
        $url = 'https://wordpress.org/latest.zip';
        $build = $this->getOption('build');
        if(!empty($build) && in_array($build, [ 'd', 'n', 'dev', 'develop',  'development', 'night', 'nightly'])) {
            $url = 'https://wordpress.org/nightly-builds/wordpress-latest.zip';
        } elseif(!empty($build) && strpos($build, '.') !== false) {
            $url = 'https://wordpress.org/wordpress-'.$build.'.zip';
        }

        $status = get_headers($url, 1);
        $code = $status[0] ?? null;
        if(strpos($code ?? '', '200') !== FALSE) {
            $this->success('Downloading WordPress from ' . $url);
            $file->download($url);
        } else {
            $this->error($status[0]);
            $this->error('Downloading WordPress failed ' . $url);
            die();
        }
    }

    /**
     * Un-archive WordPress
     */
    protected function unArchiveWordPress() {
        $zip = new \ZipArchive;

        if ( $zip->open( $this->archiveWP ) ) {
            $this->success('Extracting WordPress');

            $location = $this->path . '/wp-unzip';

            if( ! file_exists( $location ) ) {
                mkdir($location, File::DIR_PERMISSIONS, true);
            }

            $zip->extractTo( $location );
            $zip->close();

            $this->success('Moving files to ' . $this->path);
            $download = new File($location . '/wordpress');
            $download->copyTo($this->path, false, true, $this->ignoreInDownload(), $this->getOption('verbose'));

            if(is_dir($location)) {
                rmdir($location);
            }

        } else {
            $this->error('Error opening archive file');
            die();
        }

        // Cleanup zip file
        if( file_exists( $this->archiveWP ) ) {
            unlink( $this->archiveWP );
            $this->success('Downloaded archive deleted at ' . $this->archiveWP);
        }
    }

    /**
     * @return array|null
     */
    protected function ignoreInDownload() {
        if($this->type !== 'core') {
            return null;
        }

        return [
            'wp-content/themes/twenty',
            'wp-content/plugins/akismet',
            'wp-content/plugins/hello.php',
        ];
    }

    /**
     * Clean WordPress Themes
     */
    protected function cleanWordPress() {
        if($this->type == 'cleanup') {
            $this->cleanWordPressThemes();
            $this->cleanWordPressPlugins();
            $this->info('Cleanup completed. Default WordPress themes and plugins removed.');
        }

        return false;
    }

    /**
     * Clean WordPress Themes
     */
    protected function cleanWordPressThemes() {
        $twentyThemes = glob( $this->path . "/wp-content/themes/twenty*/");

        foreach ($twentyThemes as $value) {

            // Message
            $this->success('Deleting ' . $value);
            if( Str::starts( $this->path, $value) && file_exists( $value ) ) {
                // Delete theme
                ( new File($value) )->removeRecursiveDirectory();
            } else {
                $this->error('Error deleting none project file ' . $value);
            }
        }
    }

    /**
     * Clean WordPress Themes
     */
    protected function cleanWordPressPlugins() {

        $plugins = [
            $this->path . '/wp-content/plugins/akismet',
            $this->path . '/wp-content/plugins/hello.php',
        ];

        foreach ($plugins as $value) {

            // Message
            $this->success('Deleting ' . $value);

            if( Str::starts( $this->path, $value) && file_exists( $value ) ) {
                ( new File($value) )->removeRecursiveDirectory();
            } else {
                $this->error('Error deleting none project file ' . $value);
            }
        }
    }
}