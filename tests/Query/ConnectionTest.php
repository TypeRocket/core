<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Connection;
use TypeRocket\Database\Query;
use TypeRocket\Models\Model;

$my_test_connection = new \wpdb(TYPEROCKET_ALT_DATABASE_USER, TYPEROCKET_ALT_DATABASE_PASSWORD, TYPEROCKET_ALT_DATABASE_DATABASE, TYPEROCKET_ALT_DATABASE_HOST);
$my_test_connection->users = 'my_connection_users';
$my_test_connection->prefix = 'my_connection_';

Connection::getFromContainer()->addFromConfig('my-db-test', [
    'driver' => '\TypeRocket\Database\Connectors\CoreDatabaseConnector',
    'username' => TYPEROCKET_ALT_DATABASE_USER,
    'password' => TYPEROCKET_ALT_DATABASE_PASSWORD,
    'database' => TYPEROCKET_ALT_DATABASE_DATABASE,
    'host' => TYPEROCKET_ALT_DATABASE_HOST,
    'callback' => function(\wpdb $wpdb) {
        $wpdb->users = 'my_connection_users_alt';
        $wpdb->prefix = 'my_connection_alt_';
    }
]);

Connection::getFromContainer()->addFromConfig('my-db-test-real', [
    'driver' => '\TypeRocket\Database\Connectors\CoreDatabaseConnector',
    'username' => TYPEROCKET_ALT_DATABASE_USER,
    'password' => TYPEROCKET_ALT_DATABASE_PASSWORD,
    'database' => TYPEROCKET_ALT_DATABASE_DATABASE,
    'host' => TYPEROCKET_ALT_DATABASE_HOST,
    'callback' => function(\wpdb $wpdb) {
        $wpdb->users = 'my_connection_users_alt';
        $wpdb->prefix = 'some_';
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

class MyModelRealAltTableConnection extends Model
{
    protected $idColumn = 'id';
    protected $resource = 'some_table';
    protected $connection = 'alt';
}

class MyModelRealTableConnection extends Model
{
    protected $idColumn = 'id';
    protected $resource = 'table';
    protected $connection = 'my-db-test-real';
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

    public function testModelQueryCustomRealConfigConnection()
    {
        $model = new MyModelRealTableConnection;
        $model->id = 1;
        $model->text_field = 'test';
        $model->create();
        $result = $model->take(1)->findById(1);
        $sql = (string) $model->getQuery();
        $this->assertTrue($result instanceof MyModelRealTableConnection);
        $this->assertTrue($result->id === '1');
        $this->assertStringContainsString($sql, 'SELECT * FROM `some_table` WHERE `some_table`.`id` = 1 LIMIT 1 OFFSET 0');
    }

    public function testModelQueryCustomRealAltConfigConnection()
    {
        $model = new MyModelRealAltTableConnection;
        $model->id = 1;
        $model->text_field = 'test';
        $model->create();
        $result = $model->take(1)->findById(1);
        $sql = (string) $model->getQuery();
        $this->assertTrue($result instanceof MyModelRealAltTableConnection);
        $this->assertTrue($result->id === '1');
        $this->assertStringContainsString($sql, 'SELECT * FROM `some_table` WHERE `some_table`.`id` = 1 LIMIT 1 OFFSET 0');
    }

}