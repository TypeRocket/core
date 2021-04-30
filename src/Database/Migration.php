<?php
namespace TypeRocket\Database;

abstract class Migration
{
    /** @var \wpdb */
    protected $database;
    protected $prefix;

    public function __construct(\wpdb $database)
    {
        $this->database = $database;
        $this->prefix = $database->prefix;
    }

    public function run($type)
    {
        if($type == 'up') {
            $this->up();
        } else {
            $this->down();
        }

    }

    abstract function up();
    abstract function down();
}