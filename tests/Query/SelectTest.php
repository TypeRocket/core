<?php
namespace Query;

class SelectTest extends \PHPUnit_Framework_TestCase
{

    public function testSelectWithUppercaseIdColumn()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $query->select('post_title', 'ID')->where('ID', 1)->get();
        $sql = "SELECT post_title,ID FROM wp_posts WHERE ID = '1'";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testSelectWithTake()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->select('post_title', 'ID')->take(10)->where('ID', 1)->get();
        $sql = "SELECT post_title,ID FROM wp_posts WHERE ID = '1' LIMIT 10 OFFSET 0";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testCountWithWhere()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->take(10)->where('ID', 1)->count();
        $sql = "SELECT COUNT(*) FROM wp_posts WHERE ID = '1' LIMIT 10 OFFSET 0";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testSelectReturnsResults()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $result = $query->select('post_title', 'ID')->where('ID', 1)->get();
        $this->assertInstanceOf( \TypeRocket\Database\Results::class , $result );
    }

}