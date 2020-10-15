<?php
namespace TypeRocket\Http;

use TypeRocket\Utility\RuntimeCache;

class ErrorCollection
{
    public const KEY = Redirect::KEY_ERROR;

    /**
     * @var array|null
     */
    protected $errors = null;

    /**
     * @var RuntimeCache
     */
    protected $cache;

    public function __construct()
    {
        $errors = Cookie::new()->getTransient(static::KEY, false);
        $this->errors = $errors;
    }

    /**
     * @return array|null
     */
    public function errors()
    {
        Cookie::new()->deleteTransient(static::KEY);

        return $this->errors;
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }

    /**
     * @return static
     */
    public static function getFromRuntimeCache()
    {
        return RuntimeCache::getFromContainer()->get(static::KEY);
    }
}