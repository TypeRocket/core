<?php

namespace TypeRocket\Http;

use TypeRocket\Utility\Validator;

class Fields extends \ArrayObject
{

    public $fillable = [];

    /**
     * Load commands
     */
    public function __construct( $fields = [] ) {
        $this->exchangeArray( $fields );
    }

    /**
     * Get fillable
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Set fillable
     *
     * @param array $fillable
     */
    public function setFillable(array $fillable)
    {
        $this->fillable = $fillable;
    }

    /**
     * Validate fields
     *
     * @param $options
     * @param $modelClass
     *
     * @return \TypeRocket\Utility\Validator
     */
    public function validate($options, $modelClass)
    {
        return new Validator($options, $this, $modelClass);
    }

}