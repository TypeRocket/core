<?php

namespace TypeRocket\Utility;

class File
{

    public $existing = false;
    public $file;

    /**
     * File constructor.
     *
     * @param $file
     */
    public function __construct($file)
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
     * @param $needle
     * @param $replacement
     *
     * @return bool
     * @throws \Exception
     */
    public function replaceOnLine( $needle, $replacement)
    {
        $data = file($this->file);
        $fileContent = '';
        $found = false;
        if( $data ) {
            foreach ($data as $line ) {
                if ( strpos($line, $needle) !== false ) {
                    $found = true;
                    $fileContent .= rtrim(str_replace($needle, $replacement, $line)) . PHP_EOL;
                } else {
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
     * @param $new
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
     * @param $url
     */
    public function download( $url )
    {
        if( ! $this->existing ) {
            file_put_contents( $this->file , fopen( $url , 'r') );
        }
    }

}