<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\SqlRaw;
use TypeRocket\Models\WPPost;

class ModelFunctionsTest extends TestCase
{
    public function testCount()
    {
        $q = WPPost::new();
        $c = $q->count();
        $sql = $q->getSuspectSQL();

        $this->assertStringContainsString('SELECT COUNT(*) FROM (SELECT *FROM `wp_posts`) as tr_count_derived', $sql);
        $this->assertIsInt( (int) $c);
    }

    public function testAvg()
    {
        $q = WPPost::new();
        $c = $q->avg('ID');
        $sql = $q->getSuspectSQL();

        $this->assertStringContainsString($sql, 'SELECT AVG(`ID`) FROM `wp_posts`');
        $this->assertIsInt( (int) $c);
    }

    public function testSum()
    {
        $q = WPPost::new();
        $c = $q->sum('ID');
        $sql = $q->getSuspectSQL();

        $this->assertStringContainsString($sql, 'SELECT SUM(`ID`) FROM `wp_posts`');
        $this->assertIsInt( (int) $c);
    }

    public function testSumMultiply()
    {
        $q = WPPost::new();
        $c = (int) $q->sum('ID');
        $sql = $q->getSuspectSQL();
        $c_x = $c * 2;

        $q2 = WPPost::new();
        $c2 = (int) $q2->sum(SqlRaw::new('`ID` + `ID`'));
        $sql2 = $q2->getSuspectSQL();

        $this->assertStringContainsString($sql, 'SELECT SUM(`ID`) FROM `wp_posts`');
        $this->assertStringContainsString($sql2, 'SELECT SUM(`ID` + `ID`) FROM `wp_posts`');
        $this->assertTrue( $c_x === $c2);
    }

    public function testMin()
    {
        $q = WPPost::new();
        $c = $q->min('ID');
        $sql = $q->getSuspectSQL();

        $this->assertStringContainsString($sql, 'SELECT MIN(`ID`) FROM `wp_posts`');
        $this->assertIsInt( (int) $c);
    }

    public function testMax()
    {
        $q = WPPost::new();
        $c = $q->max('ID');
        $sql = $q->getSuspectSQL();

        $this->assertStringContainsString($sql, 'SELECT MAX(`ID`) FROM `wp_posts`');
        $this->assertIsInt( (int) $c);
    }
}