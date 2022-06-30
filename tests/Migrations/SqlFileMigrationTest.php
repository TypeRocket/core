<?php
declare(strict_types=1);

namespace Migrations;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\SqlRunner;

class SqlFileMigrationTest extends TestCase
{
    public function testCompileSql()
    {
        global $wpdb;
        $runner = new SqlRunner();
        $sql = '{!!prefix!!} / {!!charset!!} / {!!collate!!}';
        $compiled = $runner->compileQueryString($sql);
        $this->assertStringContainsString($wpdb->prefix, $compiled);
        $this->assertStringContainsString($wpdb->charset, $compiled);
        $this->assertStringContainsString($wpdb->collate, $compiled);
    }
}