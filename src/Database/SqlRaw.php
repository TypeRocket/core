<?php

namespace TypeRocket\Database;

class SqlRaw implements \Stringable
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
    public function __toString(): string
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