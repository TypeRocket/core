<?php

namespace TypeRocket\Database\Connectors;

class WordPressCoreDatabaseConnector extends DatabaseConnector
{
    public function connect(?string $name = null, array $args = [])
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $this->name = 'wp';
        $this->wpdb = $wpdb;

        return $this;
    }
}