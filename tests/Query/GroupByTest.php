<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;

class GroupByTest extends TestCase
{
    public function testGroupByString()
    {
        $query = new Query('some_table');
        $sql = (string) $query->groupBy('name');

        $this->assertStringContainsString($sql, 'SELECT * FROM `some_table` GROUP BY `name` ');
    }

    public function testGroupByArray()
    {
        $query = new Query('some_table');
        $sql = (string) $query->groupBy(['name','email']);

        $this->assertStringContainsString($sql, 'SELECT * FROM `some_table` GROUP BY `name`, `email` ');
    }
}