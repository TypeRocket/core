<?php

namespace Query;

use TypeRocket\Database\Query;

class InsertTest extends \PHPUnit_Framework_TestCase
{

    public function testMultipleInsert()
    {
        $query = new Query();
        $query->run = false;
        $result = $query->table('mock_create')->create(['posts_id', 'terms_id'], [
            [ 'post', 'term' ],
            [ 'post2', 'term2' ],
            [ 'post3', 'term2' ]
        ]);

        $sql = "INSERT INTO mock_create (posts_id,terms_id)  VALUES  ( 'post','term' ) , ( 'post2','term2' ) , ( 'post3','term2' ) ";

        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

}