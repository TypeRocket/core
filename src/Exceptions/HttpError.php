<?php
namespace TypeRocket\Exceptions;

class HttpError extends \Requests_Exception_HTTP
{
    /**
     * Get WP Error
     *
     * @param int $code
     * @param null|string $message
     * @return mixed
     */
    public static function abort($code = 404, $message = null)
    {
        $class = static::get_class($code);
        throw new $class($message);
    }
}