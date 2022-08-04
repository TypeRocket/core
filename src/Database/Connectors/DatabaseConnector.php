<?php

namespace TypeRocket\Database\Connectors;

abstract class DatabaseConnector
{
    protected string $name;
    protected \wpdb $wpdb;

    /**
     * @return bool
     */
    public function isConnected() : bool
    {
        return isset($this->name, $this->wpdb);
    }

    /**
     * @param string|null $name
     * @param array $args
     * @return static
     */
    abstract public function connect(?string $name = null, array $args = []);

    /**
     * @return string
     */
    public function getName() :  string
    {
        return $this->name;
    }

    /**
     * @return \wpdb
     */
    public function getConnection(): \wpdb
    {
        return $this->wpdb;
    }
}