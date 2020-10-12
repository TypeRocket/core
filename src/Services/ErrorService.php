<?php
namespace TypeRocket\Services;

class ErrorService extends Service
{
    protected $alias = 'tr-error';
    const WHOOPS = 'TypeRocketPro\Features\Whoops';

    /**
     * ErrorService constructor.
     *
     * @throws \ErrorException
     */
    public function __construct()
    {
        if(
            !immutable('DOING_AJAX', false) &&
            tr_config('app.debug') &&
            tr_config('app.errors.whoops', true) &&
            !isset($_GET['wps_disable']) &&
            class_exists(static::WHOOPS)
        )
        {
            $this->whoops();
        }
        elseif(tr_config('app.errors.throw', true))
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
        }, tr_config('app.errors.level', E_ERROR | E_PARSE));
    }
}