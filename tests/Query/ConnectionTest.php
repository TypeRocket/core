<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Connection;
use TypeRocket\Database\Query;
use TypeRocket\Models\Model;

$my_test_connection = new \wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
$my_test_connection->users = 'my_connection_users';
$my_test_connection->prefix = 'my_connection_';

Connection::getFromContainer()->addFromConfig('my-db-test', [
    'driver' => '\TypeRocket\Database\Connectors\CoreDatabaseConnector',
    'username' => DB_USER,
    'password' => DB_PASSWORD,
    'database' => DB_NAME,
    'host' => DB_HOST,
    'callback' => function(\wpdb $wpdb) {
        $wpdb->users = 'my_connection_users_alt';
        $wpdb->prefix = 'my_connection_alt_';
    }
]);

class MyModelConnection extends Model
{
    protected $idColumn = 'my_id';
    protected $resource = 'my_model';

    /**
     * @param \wpdb $wpdb
     * @return Query
     */
    public function setupQueryConnectionForModel(\wpdb $wpdb)
    {
        global $my_test_connection;

        return (new Query)->setWpdb($my_test_connection);
    }

}

class MyModelConfigConnection extends Model
{
    protected $idColumn = 'my_id';
    protected $resource = 'my_model';
    protected $connection = 'my-db-test';
}

class ConnectionTest extends TestCase
{
    public function testQueryCustomConnection()
    {
        global $my_test_connection;

        $query = (new Query)->setWpdb($my_test_connection);
        $query->table($my_test_connection->users);
        $sql = (string) $query->findById(1);
        $this->assertStringContainsString($sql, 'SELECT * FROM `my_connection_users` WHERE `my_connection_users`.`id` = 1 LIMIT 1 OFFSET 0');
    }

    public function testModelQueryCustomConnection()
    {
        $model = new MyModelConnection;
        $model->getQuery()->run = false;
        $sql = (string) $model->findById(1);
        $this->assertStringContainsString($sql, 'SELECT * FROM `my_connection_my_model` WHERE `my_connection_my_model`.`my_id` = 1 LIMIT 1 OFFSET 0');
    }

    public function testQueryCustomConfigConnection()
    {
        $my_test_connection = Connection::getFromContainer()->get('my-db-test');

        $query = (new Query)->setWpdb($my_test_connection);
        $query->table($my_test_connection->users);
        $sql = (string) $query->findById(1);
        $this->assertStringContainsString($sql, 'SELECT * FROM `my_connection_users_alt` WHERE `my_connection_users_alt`.`id` = 1 LIMIT 1 OFFSET 0');
    }

    public function testModelQueryCustomConfigConnection()
    {
        $model = new MyModelConfigConnection;
        $model->getQuery()->run = false;
        $sql = (string) $model->findById(1);
        $this->assertStringContainsString($sql, 'SELECT * FROM `my_connection_alt_my_model` WHERE `my_connection_alt_my_model`.`my_id` = 1 LIMIT 1 OFFSET 0');
    }

}