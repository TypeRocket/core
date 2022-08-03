<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;
use TypeRocket\Models\Model;

$my_test_connection = new \wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
$my_test_connection->users = 'my_connection_users';
$my_test_connection->prefix = 'my_connection_';

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

        return (new Query)->setConnection($my_test_connection);
    }

}

class ConnectionTest extends TestCase
{
    public function testQueryCustomConnection()
    {
        global $my_test_connection;

        $query = (new Query)->setConnection($my_test_connection);
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

}