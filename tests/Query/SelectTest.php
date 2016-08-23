<?php
class SelectTest extends PHPUnit_Framework_TestCase
{

    public function testSimpleSelect()
    {
        require BASE_WP;

        $query = new \TypeRocket\Database\Query();
        $query->table = 'wp_posts';
        $query->idColumn = 'ID';

        $result = $query->select('post_title', 'ID')->where('ID', 1)->get();

        var_dump($query->lastCompiledSQL);
        var_dump($result);

        $this->assertTrue( $query->lastCompiledSQL == 'SELECT post_title, ID FROM wp_posts WHERE ID = \'1\'' );
    }

}