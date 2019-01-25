<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;


class WhereTest extends TestCase
{

    public function testRawWhereTitle()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->where('ID', 1)
            ->appendRawWhere('AND', "post_status = 'publish'")
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE wp_posts SET post_title='My Title' WHERE ID = 1 AND post_status = 'publish'";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testOnlyRawWhereTitle()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->appendRawWhere('AND', "post_status = 'publish'")
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE wp_posts SET post_title='My Title' WHERE post_status = 'publish'";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testMultipleRawWhereTitle()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->appendRawWhere('AND', "post_status = 'publish'")
            ->appendRawWhere('OR', "(post_title = 'Hello World!' AND ID = 1)")
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE wp_posts SET post_title='My Title' WHERE post_status = 'publish' OR (post_title = 'Hello World!' AND ID = 1)";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testWhereResetTitle()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->where('ID', 1)
            ->appendRawWhere('AND', "post_status = 'publish'")
            ->resetWhere()
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE wp_posts SET post_title='My Title'";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }
}