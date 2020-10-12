<?php
namespace TypeRocket\Exceptions;

class HttpError extends \Requests_Exception_HTTP
{
    /**
     * Get WP Error
     *
     * @param int $code
     * @param null $message
     * @return mixed
     */
    public function getRealError($code = 404, $message = null)
    {
        $class = static::get_class($code);
        return new $class($message);
    }
}