<?php

namespace TypeRocket\Database;

use TypeRocket\Core\Config;
use TypeRocket\Core\Container;
use TypeRocket\Database\Connectors\DatabaseConnector;
use TypeRocket\Database\Connectors\WordPressCoreDatabaseConnector;

class Connection
{
    const ALIAS = 'db-connection';

    /**
     * @var array<string, \wpdb>
     */
    protected array $connections = [];

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
     * @param null|string $name
     * @return $this
     */
    public function addFromConfig(?string $name, ?array $config = null)
    {
        if(is_null($config) || is_null($name)) {
            $drivers = Config::getFromContainer()->locate('database.drivers');
            $config = $drivers[$name] ?? Config::getFromContainer()->locate('database.default');

            if($name && !$drivers && !$config) {
                throw new \Error(__("TypeRocket database connection configuration not found for \"{$name}\"", 'typerocket-core'));
            }
        }

        /** @var DatabaseConnector $connector */
        $connector = new $config['driver'];
        $connector->connect($name, $config);
        return $this->add($connector->getName(), $connector->getConnection());
    }

    /**
     * @param string $name
     * @return \wpdb
     */
    public function getOrAddFromConfig(string $name, ?array $config = null)
    {
        if(!array_key_exists($name, $this->connections)) {
            $this->addFromConfig($name, $config);
        }

        return $this->connections[$name];
    }

    /**
     * @param ?string $name
     * @return \wpdb
     */
    public function get(?string $name, ?string $fallback = null)
    {
        if(!array_key_exists($name, $this->connections) && is_null($fallback)) {
            $this->addFromConfig($name);
        }

        return $this->connections[$name] ?? $this->connections[$fallback];
    }

    /**
     * @param string|null $name
     * @return bool
     */
    public function exists(?string $name) : bool
    {
        return !is_null($name) && array_key_exists($name, $this->connections);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function close(string $name)
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
    public function default()
    {
        $default = Config::getFromContainer()->locate('database.default', 'wp');
        return $this->get($default);
    }

    /**
     * @return static
     */
    public static function initDefault()
    {
        $default = Config::getFromContainer()->locate('database.default');
        $connection = new static;

        if(!$connection->exists($default)) {
            $config = null;

            if(is_null($default)) {
                $default = 'wp';
                $config = [
                    'driver' => WordPressCoreDatabaseConnector::class
                ];
            }

            $connection->addFromConfig($default, $config);
        }

        return $connection;
    }

    /**
     * @return static
     */
    public static function getFromContainer()
    {
        return Container::resolve(static::ALIAS);
    }
}