<?php

namespace TypeRocket\Database;

class SqlRaw
{
    protected string $sql = '';

    /**
     * SQL Raw constructor.
     *
     * @param string $sql
     */
    public function __construct(string $sql)
    {
        $this->sql = $sql;
    }

    /**
     * To String
     *
     * @return string|null
     */
    public function __toString()
    {
        return $this->sql;
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