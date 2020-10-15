<?php
namespace TypeRocket\Utility;

use TypeRocket\Core\Config;
use TypeRocket\Services\ErrorService;

/**
 * Class ExceptionReport
 * @package TypeRocket\Utility
 */
class ExceptionReport
{
    /** @var \Throwable  */
    protected $exception;
    /**
     * @var bool
     */
    protected $debug;

    /**
     * ExceptionReport constructor.
     *
     * Be aware of WP_Fatal_Error_Handler as it will catch errors before
     * this in some cases.
     *
     * @param \Throwable $exception
     * @param bool $debug
     */
    public function __construct(\Throwable $exception, $debug = false)
    {
        $this->exception = $exception;
        $this->debug = $debug;
    }

    /**
     * Report
     * @throws \Throwable
     */
    public function report()
    {
        if(Config::get('app.debug') || $this->debug) {
            if(class_exists(ErrorService::WHOOPS)) {
                $this->whoops();
            }

            $this->basic();
        }
    }

    /**
     * Basic Reporting
     *
     * @throws \Throwable
     */
    protected function basic() {
        throw $this->exception;
    }

    /**
     * Whoops
     *
     * @throws \Throwable
     */
    protected function whoops()
    {
        throw $this->exception;
    }
}