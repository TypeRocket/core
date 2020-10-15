<?php
namespace TypeRocket\Utility;

class PersistentCache
{
    protected $path = null;

    /**
     * PersistentCache constructor.
     *
     * @param string $folder
     */
    public function __construct($folder = 'app')
    {
        $this->path = \TypeRocket\Core\Config::get('paths.cache') . "/$folder";

        if(!is_dir( $this->path )) {
            mkdir( $this->path, 0777, true );
        }
    }

    /**
     * @param string $folder
     *
     * @return static
     */
    public static function new($folder = 'app')
    {
        return new static($folder);
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        try {
            return $this->getData($this->getFile($key)) ?? Value::get($default);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $key
     *
     * @return int
     */
    public function getSecondsRemaining($key)
    {
        return $this->getFileExpireTime($this->getFile($key)) - time();
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasExpired($key)
    {
        return $this->fileHasExpired($this->getFile($key));
    }

    /**
     * @param string $key
     * @param mixed $default
     * @param \DateTime|string|int $time cache forever by default
     *
     * @return mixed
     */
    public function getOtherwisePut($key, $default, $time = 9999999999)
    {
        $data = $this->get($key);

        if(!$data) {
            try {
                $data = Value::get($default);
                $this->put($key, $data, $time);
            } catch (\ReflectionException $e) {
                return null;
            }
        }

        return $data;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateTime|string|int $time cache forever by default
     *
     * @return File
     */
    public function put($key, $value, $time = 9999999999)
    {
        return $this->getFile($key)->replace($this->encodeData($value, $time));
    }

    /**
     * @param $key
     */
    public function remove($key)
    {
        $this->getFile($key)->remove();
    }

    /**
     * @param File $file
     *
     * @return mixed|null
     */
    protected function getData(File $file)
    {
        if($this->fileHasExpired($file)) {
            return null;
        }

        return unserialize($file->readFirstCharactersTo(null, 10));
    }

    protected function getFile($key)
    {
        return File::new($this->cacheFilePath($key));
    }

    /**
     * @param mixed $value
     * @param \DateTime|string|int $time
     *
     * @return string
     */
    protected function encodeData($value, $time) {
        return $this->createExpireTime($time) . serialize($value);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function cacheFilePath($key)
    {
        return $this->path . '/' . sha1($key);
    }

    /**
     * @param \DateTime|string|int $time
     *
     * @return false|int
     */
    protected function createExpireTime($time)
    {
        if(is_int($time)) {
            $time = time() + $time;
            $max = 9999999999;

            return $time >= $max ? $max : $time;
        }

        if($time instanceof \DateTime) {
            return $time->getTimestamp();
        }

        return strtotime($time);
    }

    /**
     * @param File $file
     *
     * @return bool
     */
    protected function fileHasExpired(File $file)
    {
        $expired = time() > $this->getFileExpireTime($file);

        if($expired && $file->exists()) {
            $file->remove();
        }

        return $expired;
    }

    /**
     * @param File $file
     *
     * @return int
     */
    protected function getFileExpireTime(File $file)
    {
        if($file->exists()) {
            return (int) $file->readFirstCharactersTo(10);
        }

        return 0;
    }
}