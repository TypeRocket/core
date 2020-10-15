<?php
namespace TypeRocket\Utility;

class Manifest
{
    /**
     * Cache Manifest File
     *
     * @param string $path
     * @param string $namespace
     * @return mixed
     */
    public static function cache($path, $namespace) {
        $manifest = [];

        try {
            $manifest = json_decode(file_get_contents($path), true);
            RuntimeCache::getFromContainer()->add('manifest', $manifest, $namespace);
        } catch (\Exception $e) {
            Helper::reportError($e, true);
        }

        return $manifest;
    }

    /**
     * @return RuntimeCache
     */
    public static function typerocket()
    {
        return static::getFromRuntimeCache();
    }

    /**
     * Get Manifest From Runtime Cache
     *
     * @param string $namespace
     * @return mixed
     */
    public static function getFromRuntimeCache($namespace = 'typerocket')
    {
        return \TypeRocket\Utility\RuntimeCache::getFromContainer()->get('manifest', $namespace);
    }
}