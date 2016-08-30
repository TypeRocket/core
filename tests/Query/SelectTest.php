<?php
namespace Query;

class SelectTest extends \PHPUnit_Framework_TestCase
{

    public function testSelectWithUppercase()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table = 'wp_posts';
        $query->idColumn = 'ID';
        $query->select('post_title', 'ID')->where('ID', 1)->get();
        $this->assertTrue( $query->lastCompiledSQL == "SELECT `post_title`,`ID` FROM wp_posts WHERE ID = '1'" );
    }

    public function testSelectReturnsResults()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table = 'wp_posts';
        $query->idColumn = 'ID';
        $result = $query->select('post_title', 'ID')->where('ID', 1)->get();
        $this->assertInstanceOf( \TypeRocket\Database\Results::class , $result );
    }

}