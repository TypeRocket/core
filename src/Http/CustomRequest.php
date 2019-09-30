<?php


namespace TypeRocket\Http;


class CustomRequest extends Request
{

    /**
     * CustomRequest constructor.
     * @param null|array|string $method
     * @param bool $hook
     * @param bool $rest
     * @param bool $custom
     */
    public function __construct($method = null, $hook = false, $rest = false, $custom = false )
    {
        parent::__construct($method, $hook, $rest, $custom);

        if( is_array($method) ) {
            foreach ($method as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}