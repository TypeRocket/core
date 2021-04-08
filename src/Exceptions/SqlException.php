<?php
namespace TypeRocket\Exceptions;

class SqlException extends \Exception
{
    protected $sql;
    protected $sqlError;

    public function setSql($sql)
    {
        $this->sql = $sql;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function setSqlError($sql)
    {
        $this->sqlError = $sql;
    }

    public function getSqlError()
    {
        return $this->sqlError;
    }
}