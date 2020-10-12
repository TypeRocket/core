<?php
namespace TypeRocket\Http;

class CustomRequest extends Request
{
    /**
     * CustomRequest constructor.
     * @param array $options
     */
    public function __construct($options = null)
    {
        parent::__construct();

        if( is_array($options) ) {
            foreach ($options as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}