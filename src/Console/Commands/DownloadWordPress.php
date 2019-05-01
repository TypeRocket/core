<?php


namespace TypeRocket\Console\Commands;


use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Str;

class DownloadWordPress extends Command
{
    protected $archiveWP;
    protected $path;
    protected $clean = 'all';

    protected $command = [
        'wp:download',
        'Download WordPress',
        'This command downloads WordPress and unzips it.'
    ];

    protected function config()
    {
        $this->archiveWP = TR_PATH . '/wp.zip';
        $this->addArgument('clean', self::OPTIONAL, 'Remove all WordPress themes and plugins');
        $this->addArgument('path', self::OPTIONAL, 'The absolute path where WP will download');
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy use:templates {wp-content}
     *
     * @return void
     */
    protected function exec()
    {
        $path = $this->getArgument('path');
        $this->clean = $this->getArgument('clean') ?? 'all';
        $this->path = $path ? rtrim( $path, '/') : TR_PATH;

        $this->downloadWordPress();
        $this->unArchiveWordPress();
        $this->cleanWordPress();
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

            $zip->extractTo( $this->path );
            $zip->close();
        } else {
            // Error
            $this->error('Error opening archive file');
            die();
        }

        // Cleanup zip file
        if( file_exists( $this->archiveWP ) ) {
            unlink( $this->archiveWP );
            $this->success('Archive file deleted');
        }
    }

    /**
     * Clean WordPress Themes
     */
    protected function cleanWordPress() {

        $remove_themes = false;
        $remove_plugins = false;

        if($this->clean == 'themes') { $remove_themes = true; }
        if($this->clean == 'plugins') { $remove_plugins = true; }
        if($this->clean == 'all') { $remove_themes = $remove_plugins = true; }

        if($remove_themes) {
            $this->cleanWordPressThemes();
        }

        if($remove_plugins) {
            $this->cleanWordPressPlugins();
        }

        if($remove_plugins || $remove_themes) { return true; }

        $this->success('Cleanup skipped. Themes and plugins retained.');
        return false;
    }

    /**
     * Clean WordPress Themes
     */
    protected function cleanWordPressThemes() {

        $twentyThemes = glob( $this->path . "/wordpress/wp-content/themes/twenty*/");

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
            $this->path . '/wordpress/wp-content/plugins/akismet',
            $this->path . '/wordpress/wp-content/plugins/hello.php',
        ];

        foreach ($plugins as $value) {

            // Message
            $this->success('Deleting ' . $value);
            if( Str::starts( TR_PATH, $value) && file_exists( $value ) ) {
                // Delete plugins
                ( new File($value) )->removeRecursiveDirectory();
            } else {
                $this->error('Error deleting none project file ' . $value);
            }
        }
    }
}