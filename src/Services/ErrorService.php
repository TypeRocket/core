<?php
namespace TypeRocket\Services;

use TypeRocket\Core\Config;

class ErrorService extends Service
{
    public const ALIAS = 'tr-error';
    public const WHOOPS = 'TypeRocketPro\Features\Whoops';

    /**
     * ErrorService constructor.
     *
     * @throws \ErrorException
     */
    public function __construct()
    {
        if(
            !Config::env('DOING_AJAX', false) &&
            Config::get('app.debug') &&
            Config::get('app.errors.whoops', true) &&
            !isset($_GET['wps_disable']) &&
            class_exists(static::WHOOPS)
        )
        {
            $this->whoops();
        }
        elseif(Config::get('app.errors.throw', true))
        {
            $this->php();
        }
    }

    /**
     * Whoops PHP
     */
    protected function whoops() {
        $class = static::WHOOPS;
        new $class;
    }

    /**
     * @throws \ErrorException
     */
    protected function php() {
        set_error_handler(function ($num, $str, $file, $line) {
            throw new \ErrorException($str, 0, $num, $file, $line);
        }, Config::get('app.errors.level', E_ERROR | E_PARSE));
    }
}