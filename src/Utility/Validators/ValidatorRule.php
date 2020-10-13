<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

abstract class ValidatorRule
{
    protected $error;
    protected $success;
    protected $args;
    public CONST KEY = 'map_key';

    /**
     * @param array $args
     *
     * @return $this
     */
    public function setArgs(array $args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * Validate
     *
     * Args typically available from $this->args
     *
     * @var $option
     * @var $option2
     * @var $option3
     * @var $full_name
     * @var $field_name
     * @var $value
     * @var $type
     * @var Validator $validator
     *
     * @return bool
     */
    public abstract function validate() : bool;

    public function getError()
    {
        return $this->error;
    }

    /**
     * Return
     *
     * @return array
     */
    public function return()
    {
        return [
            'error' => $this->error,
            'success' => $this->success,
        ];
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
}