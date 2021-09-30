<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;

class OrderByTest extends TestCase
{
    public function testCreateWithSlashing()
    {
        $query = new Query('some_table');
        $ids = [123,456,987,0];
        $options = '"' . implode('","', $ids) . '"';
        $sql = (string) $query->appendRawOrderBy("FIELD(id, {$options})");

        $this->assertTrue($sql === 'SELECT * FROM `some_table` ORDER BY FIELD(id, "123","456","987","0")');
    }
}