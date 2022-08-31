<?php
namespace TypeRocket\Utility;

class File
{
    public $existing = false;
    public $wrote;
    public $verbose;
    public $file;
    public $isDir;
    public $dirPermissions;
    public const DIR_PERMISSIONS = 0755;

    /**
     * File constructor.
     *
     * @param string $file
     * @param null|int $folder_permissions
     */
    public function __construct( $file, $folder_permissions = null )
    {
        $this->file = $file;
        $this->dirPermissions = $folder_permissions ?? static::DIR_PERMISSIONS;
        $this->isDir = is_dir($this->file);

        if( file_exists( $file ) ) {
            $this->existing = true;

            if($this->exists()) {
                $this->file = realpath($file);
            }
        }
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public static function new($file)
    {
        return new static(...func_get_args());
    }

    /**
     * @return null|string
     */
    public function file()
    {
        return $this->file;
    }

    /**
     * File Exists
     *
     * @return bool
     */
    public function exists()
    {
        return $this->existing;
    }

    /**
     * @return mixed
     */
    public function wrote()
    {
        return $this->wrote;
    }

    /**
     * Create File
     *
     * @param null|string $content
     *
     * @return File
     */
    public function create($content = null)
    {
        $this->tryToMakeFileWithDir();

        if($content) {
            $this->wrote = (bool) file_put_contents($this->file, $content);
        }

        return $this;
    }

    /**
     * @return false|string
     */
    public function getContainingFolder()
    {
        $file_name = basename($this->file);
        return rtrim(substr($this->file, 0, -strlen($file_name)), '/\\');
    }

    /**
     * @param string $name new file name
     * @param bool $sanitize sanitize file name
     *
     * @return bool
     * @throws \Exception
     */
    public function rename($name, $sanitize = true)
    {
        if($this->existing) {
            $name = $sanitize ? Sanitize::dash($name, true) : $name;
            $to = $this->getContainingFolder() . '/' . $name;
            $this->echoVerbose("Rename: {$this->file} >> {$to}");
            return rename($this->file,$to);
        }

        throw new \Exception('File or folder must exist to rename.');
    }

    /**
     * @param string $destination
     */
    protected function tryToMakeDir($destination, $verbos = 2)
    {
        if(!is_dir($destination)) {
            $file_name = basename($destination);
            $destination = substr($destination, 0, -strlen($file_name));
        }

        if (!is_dir($destination)) {
            $this->echoVerbose("Make dir {$this->dirPermissions}: {$destination}", $verbos);

            return $this->makeDir($destination);
        }

        return false;
    }

    /**
     * @param string $directory
     * @param null|int $premissions
     *
     * @return bool
     */
    public function makeDir($directory, $premissions = null)
    {
        return mkdir($directory, $premissions ?? $this->dirPermissions, true);
    }

    /**
     * @param string|null $destination
     */
    protected function tryToMakeFileWithDir($destination = null)
    {
        if(!$this->existing) {
            $destination = $destination ?? $this->file;
            $this->tryToMakeDir($destination);
            if($fp = fopen($destination, 'w')) {
                fclose($fp);
                $this->existing = true;
            }
        }
    }

    /**
     * Create File
     *
     * @param null|string $content
     *
     * @return File
     */
    public function append($content = null)
    {
        $this->tryToMakeFileWithDir();

        if($content) {
            $this->wrote = (bool) file_put_contents($this->file, $content, FILE_APPEND);
        }

        return $this;
    }

    /**
     * Remove File
     *
     * @return bool
     */
    public function remove()
    {
        if($this->existing) {
            $this->existing = !unlink($this->file);
            return !$this->existing;
        }

        return true;
    }

    /**
     * Replace File
     *
     * @param $content
     *
     * @return File|null
     */
    public function replace($content)
    {
        if(!$this->remove()) {
            return null;
        }

        $this->create($content);

        return $this;
    }

    /**
     * Read File
     *
     * @return false|string|null
     */
    public function read()
    {
        if(!$this->existing) {
            return null;
        }

        return file_get_contents($this->file);
    }

    /**
     * Read First Line of File
     *
     * @return false|string|null
     */
    public function readFirstLine()
    {
        if(!$this->existing) {
            return null;
        }

        if($fp = fopen($this->file, 'r')) {
            $line = fgets($fp);
            fclose($fp);
            return $line;
        }

        return '';
    }

    /**
     * Read First Few Characters
     *
     * This is not Unicode safe because PHP does not support Unicode.
     * This will work only with 256-character set, and will not
     * work correctly with multi-byte characters.
     *
     * @param int $length
     * @param int|null $offset
     *
     * @return false|string|null
     */
    public function readFirstCharactersTo($length = null, $offset = 0)
    {
        if(!$this->existing) {
            return null;
        }

        if(is_null($length)) {
            return file_get_contents($this->file, false, null, $offset);
        }

        return file_get_contents($this->file, false, null, $offset, $length);
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
                $this->wrote = (bool) file_put_contents($this->file, $fileContent);
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
     * @param string $new new file path
     * @param array $tags array of strings
     * @param array $replacements array of strings
     *
     * @return bool|string
     */
    public function copyTemplateFile( $new, $tags = [], $replacements = [] )
    {
        $newContent = $this->getReplacmentTemplateContent($tags, $replacements);

        if( ! file_exists($new) ) {
            $file = fopen($new, "w") or die("Unable to open file!");
            $this->wrote = (bool) fwrite($file, $newContent);
            fclose($file);
            return realpath( $new );
        } else {
            return false;
        }
    }

    /**
     * @param array $tags array of strings
     * @param array $replacements array of strings
     *
     * @return $this|null
     * @throws \Exception
     */
    public function replaceTemplateContent($tags = [], $replacements = [])
    {
        $newContent = $this->getReplacmentTemplateContent($tags, $replacements);
        return $this->replace($newContent);
    }

    /**
     * @param array $tags array of strings
     * @param array $replacements array of strings
     *
     * @return false|string|string[]
     */
    public function getReplacmentTemplateContent($tags = [], $replacements = [])
    {
        if($this->isDir) {
            throw new \Exception('File::getReplacmentTemplateContent() requires a file.');
        }

        return str_replace($tags, $replacements, file_get_contents( $this->file ) );
    }

    /**
     * Download URL
     *
     * @param string $url
     *
     * @return bool|int
     */
    public function download( $url )
    {
        if( ! $this->existing ) {

            if($fp = fopen( $url , 'r')) {
                $content = file_put_contents( $this->file , $fp);
                fclose($fp);

                return $content;
            }
        }

        return false;
    }

    /**
     * @return string|false
     */
    public function mimeType()
    {
        if($fi = finfo_open(FILEINFO_MIME_TYPE)) {
            $info = finfo_file($fi, $this->file);
            finfo_close($fi);

            return $info;
        }

        return false;
    }

    /**
     * @return false|int
     */
    public function size()
    {
        return filesize($this->file);
    }

    /**
     * @return false|int
     */
    public function lastModified()
    {
        return filemtime($this->file);
    }

    /**
     * Remove Recursive
     *
     * Delete everything, files and folders.
     *
     * @param null|string $path
     * @param bool $removeSelf
     * @param string|null $root
     *
     * @return bool
     * @throws \Throwable
     */
    public function removeRecursiveDirectory($path = null, $removeSelf = true, $root = null )
    {
        $path = $path ? realpath($path) : $this->file;
        $project_root = Helper::wordPressRootPath();

        if( !file_exists($path) ) {
            throw new \Exception('Nothing deleted. ' . $path . ' does not exist.');
        }

        if(!$root) {
            if( !Str::starts($project_root, TYPEROCKET_PATH) ) {
                $project_root = TYPEROCKET_PATH;
            }
        }

        $root = rtrim($root ?? $project_root, DIRECTORY_SEPARATOR);

        if( !empty($root) && !Str::starts($root, $path) ) {
            throw new \Exception('Nothing deleted. ' . $path . ' file must be within your project scope or ' . $root);
        }

        $dir = rtrim($path);

        if( ! $root || $dir == $root || ! $path ) {
            throw new \Error('Nothing deleted. You can not delete your entire WordPress project!');
        }

        if( empty($dir) || $dir == '/' || $dir == '\\' ) {
            throw new \Error('Nothing deleted. You can not delete your entire server!');
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
     * Echo Messages
     *
     * @param bool $verbose
     *
     * @return $this
     */
    public function verbose($verbose = true)
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * @param string $message
     * @param int $message 2 is week and 1 is strong
     */
    public function echoVerbose($message, $level = 1)
    {
        if($this->verbose && $this->verbose !== $level) {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Copy Recursive
     *
     * This function replaces all older files. Use with caution.
     *
     * @param string $destination location file/dir will be copied to
     * @param bool $relative prefix destination location relative to the TypeRocket root
     * @param bool $delete delete old files after being copied to new location
     * @param null|array $ignore list of files or folders to ignore whose path name start with a value
     * @param bool|int $verbose output messages
     * @param bool $replace replace file or folder if already existing
     *
     * @throws \Throwable
     */
    public function copyTo($destination, $relative = false, $delete = false, $ignore = null, $verbose = null, $replace = true)
    {
        $this->verbose = $verbose ?? $this->verbose ?? false;
        $verbose = $this->verbose;
        $path = $this->file;

        if($relative) {
            $destination = TYPEROCKET_PATH . DIRECTORY_SEPARATOR . ltrim($destination, DIRECTORY_SEPARATOR);
        }

        if(!file_exists($destination) && is_dir($path)) {
            $this->tryToMakeDir($destination);
        }

        if(!is_dir($path) && is_file($path)) {
            $dont_replace_it = file_exists($destination) && !$replace;

            if(!$dont_replace_it) {
                $this->tryToMakeDir($destination);

                $this->echoVerbose($path);

                if($this->wrote = copy($path, $destination)){
                    $this->echoVerbose('Copy file: ' . $destination);
                } else {
                    $this->echoVerbose('Copy file failed: ' . $destination);
                }
            }
            elseif($verbose) {
                echo 'Kept existing file: ' . $destination . PHP_EOL;
                echo 'Wanted to add: ' . $path . PHP_EOL;
            }

            return;
        }

        $no_dots = \RecursiveDirectoryIterator::SKIP_DOTS;
        $self_first = \RecursiveIteratorIterator::SELF_FIRST;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, $no_dots), $self_first);

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $name = $iterator->getSubPathName();
            $file = $destination . DIRECTORY_SEPARATOR . $name;
            $skip = false;

            if(is_array($ignore)) {
                foreach ($ignore as $loc) {
                    if(strpos($name, $loc, 0) !== false) {
                        $this->echoVerbose('Ignoring: ' . $file, 2);

                        $skip = true;
                        break;
                    }
                }
            }

            if (!$skip && $item->isDir() && !file_exists($file) ) {
                $this->makeDir($file);
            }
            elseif(!$skip && !$item->isDir()) {
                $dont_replace_it = file_exists($file) && !$replace;

                if(!$dont_replace_it) {
                    $this->tryToMakeDir($file);

                    if($this->wrote = copy($item, $file)){
                        $this->echoVerbose('Copy file: ' . $item . ' >> ' . $file);
                    } else {
                        $this->echoVerbose('Copy file failed: ' . $item . ' >> ' . $file);
                    }
                }
                elseif($verbose) {
                    echo 'Kept existing file: ' . $destination . PHP_EOL;
                    echo 'Wanted to add: ' . $item . PHP_EOL;
                }
            }
        }

        if($delete) {
            $this->removeRecursiveDirectory();
        }
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return $this->file;
    }

}