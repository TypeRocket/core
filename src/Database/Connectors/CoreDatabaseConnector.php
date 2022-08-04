<?php

namespace TypeRocket\Database\Connectors;

class CoreDatabaseConnector extends DatabaseConnector
{
    public function connect(?string $name = null, array $args = [])
    {
        $this->name = $name;
        $this->wpdb = new \wpdb(
            $args['username'],
            $args['password'],
            $args['database'],
            $args['host']
        );

        if(isset($args['callback']) && is_callable($args['callback'])) {
            $args['callback']($this->wpdb);
        }

        return $this;
    }
}