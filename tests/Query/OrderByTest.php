<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;

class OrderByTest extends TestCase
{
    public function testCreateWithSlashing()
    {
        $query = new Query();
        $ids = [123,456,987,0];
        $options = '"' . implode('","', $ids) . '"';
        $sql = (string) $query->appendRawOrderBy("FIELD(id, {$options})");

        $this->assertTrue($sql === 'SELECT * FROM `` ORDER BY FIELD(id, "123","456","987","0")');
    }
}