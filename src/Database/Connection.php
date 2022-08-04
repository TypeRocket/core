<?php

namespace TypeRocket\Database;

use TypeRocket\Core\Config;
use TypeRocket\Core\Container;
use TypeRocket\Database\Connectors\DatabaseConnector;

class Connection
{
    const ALIAS = 'db-connection';

    /**
     * @var array<string, \wpdb>
     */
    protected $connections = [];

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->connections;
    }

    /**
     * @param string $name
     * @param \wpdb $wpdb
     * @return static
     */
    public function add(string $name, \wpdb $wpdb)
    {
        if(array_key_exists($name, $this->connections)) {
            throw new \Error(__("TypeRocket database connection name \"{$name}\" already used.", 'typerocket-core'));
        }

        $this->connections[$name] = $wpdb;

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function addFromConfig(string $name, ?array $config = null)
    {
        if(is_null($config)) {
            $drivers = Config::getFromContainer()->locate('database.drivers');
            $config = $drivers[$name];
        }

        /** @var DatabaseConnector $connector */
        $connector = new $config['driver'];
        $connector->connect($name, $config);
        return $this->add($connector->getName(), $connector->getConnection());
    }

    /**
     * @param $name
     * @return \wpdb
     */
    public function get($name)
    {
        if(!array_key_exists($name, $this->connections)) {
            $this->addFromConfig($name);
        }

        return $this->connections[$name];
    }

    /**
     * @param $name
     * @return $this
     */
    public function close($name)
    {
        if(array_key_exists($name, $this->connections)) {
            $this->connections[$name]->close();
            unset($this->connections[$name]);
        }

        return $this;
    }

    /**
     * @return \wpdb
     */
    public static function getDefault()
    {
        $driver = Config::getFromContainer()->locate('database.default');
        return static::getFromContainer()->get($driver);
    }

    /**
     * @return static
     */
    public static function getFromContainer()
    {
        return Container::resolve(static::ALIAS);
    }
}