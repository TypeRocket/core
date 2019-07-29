<?php

namespace TypeRocket\Utility;

use TypeRocket\Utility\Str;

class File
{

    public $existing = false;
    public $file;

    /**
     * File constructor.
     *
     * @param string $file
     */
    public function __construct( $file )
    {
        $this->file = $file;

        if( file_exists( $file ) ) {
            $this->existing = true;
            $this->file = realpath($file);
        }
    }

    /**
     * Replace
     *
     * @param string $needle
     * @param string $replacement
     *
     * @param bool $regex
     * @return bool
     * @throws \Exception
     */
    public function replaceOnLine( $needle, $replacement, $regex = false)
    {
        $data = file($this->file);
        $fileContent = '';
        $found = false;
        if( $data ) {

            foreach ($data as $line ) {
                if($regex && preg_match($needle, $line)) {
                    $found = true;
                    $fileContent .= rtrim(preg_replace($needle, $replacement, $line)) . PHP_EOL;
                }
                elseif ( !$regex && strpos($line, $needle) !== false ) {
                    $found = true;
                    $fileContent .= rtrim(str_replace($needle, $replacement, $line)) . PHP_EOL;
                }
                else {
                    $fileContent .= rtrim($line) . PHP_EOL;
                }
            }

            if($found) {
                file_put_contents($this->file, $fileContent);
                return true;
            } else {
                return false;
            }
        } else {
            throw new \Exception('File is empty');
        }
    }

    /**
     * Copy Template
     *
     * @param string $new
     * @param array $tags
     * @param array $replacements
     *
     * @return bool|string
     */
    public function copyTemplateFile( $new, $tags = [], $replacements = [] )
    {
        $newContent = str_replace($tags, $replacements, file_get_contents( $this->file ) );

        if( ! file_exists($new) ) {
            $file = fopen($new, "w") or die("Unable to open file!");
            fwrite($file, $newContent);
            fclose($file);
            return realpath( $new );
        } else {
            return false;
        }
    }

    /**
     * Download URL
     *
     * @param string $url
     */
    public function download( $url )
    {
        if( ! $this->existing ) {
            file_put_contents( $this->file , fopen( $url , 'r') );
        }
    }

    /**
     * Remove Recursive
     *
     * Delete everything, files and folders.
     *
     * @param null|string $path
     * @param bool $removeSelf
     *
     * @return bool
     */
    public function removeRecursiveDirectory($path = null, $removeSelf = true )
    {
        $path = $path ? $path : $this->file;

        if( Str::starts( TR_PATH, $path) && file_exists( $path ) ) {
            $path = mb_substr( $path, mb_strlen(TR_PATH) );
        }

        $dir = rtrim( TR_PATH . '/' . trim($path, '/'), '/' );

        if( ! TR_PATH || $dir == TR_PATH || ! $path ) {
            die('You are about to delete your entire project!');
        }

        if( empty($dir) || $dir == '/' || $dir == '\\' ) {
            die('You are about to delete your server!');
        }

        if(!is_dir($dir) && is_file($dir)) {
            unlink($dir);
            return true;
        }

        if( file_exists( $dir ) ) {
            $it = new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS );
            $files = new \RecursiveIteratorIterator( $it, \RecursiveIteratorIterator::CHILD_FIRST );
            foreach($files as $file) {
                if ($file->isDir()){
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }

            if($removeSelf) {
                rmdir($dir);
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Copy Recursive
     *
     * This function replaces all older files. Use with caution.
     *
     * @param string $destination location file/dir will be copied to
     * @param bool $relative prefix destination location relative to the TypeRocket root.
     */
    public function copyTo($destination, $relative = false)
    {
        $path = $this->file;

        if($relative) {
            $destination = TR_PATH . '/' . ltrim($destination, DIRECTORY_SEPARATOR);
        }

        if(!file_exists($destination) && is_dir($path)) {
            mkdir($destination, 0755);
        }

        if(!is_dir($path) && is_file($path)) {
            copy($path, $destination);
            return;
        }

        $no_dots = \RecursiveDirectoryIterator::SKIP_DOTS;
        $self_first = \RecursiveIteratorIterator::SELF_FIRST;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, $no_dots), $self_first);

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

}