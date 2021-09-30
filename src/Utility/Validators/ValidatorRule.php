<?php
namespace TypeRocket\Utility\Validators;

use TypeRocket\Utility\Validator;

abstract class ValidatorRule
{
    protected $error;
    protected $success;
    protected $fieldLabel;

    protected $args = [
        'weak' => null
    ];

    public CONST KEY = 'map_key';

    /**
     * @param array $args
     *
     * @return $this
     */
    public function setArgs(array $args)
    {
        $this->args = $args;
        $this->fieldLabel = $args['field_label'] ?? null;

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
     * @var $weak
     * @var $full_name
     * @var $field_name
     * @var $value
     * @var $type
     * @var Validator $validator
     *
     * @return bool
     */
    public abstract function validate() : bool;

    /**
     * Get Error Message
     *
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get Field Name
     *
     * @return mixed
     */
    public function getFieldLabel()
    {
        return $this->fieldLabel;
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
     * @return bool
     */
    public function isOptional() : bool
    {
        return (bool) ($this->args['weak'] ?? null);
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