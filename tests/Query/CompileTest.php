<?php

namespace Query;

class CompileTest extends \PHPUnit_Framework_TestCase
{
    public function testUpdatePostTitle()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table = 'wp_posts';
        $query->idColumn = 'ID';
        $query->where('ID', 1)->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `wp_posts`.`post_title`='My Title' WHERE `wp_posts`.`ID` = '1'";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }
}