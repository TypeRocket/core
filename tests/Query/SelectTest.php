<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;

class SelectTest extends TestCase
{

    public function testSelectWithUppercaseIdColumn()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $query->select('post_title', 'ID')->where('ID', 1)->get();
        $sql = "SELECT `post_title`,`ID` FROM `wp_posts` WHERE `ID` = 1";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testSelectWithUppercaseIdColumnTicks()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $query->select('`wp_posts`.`post_title` as name', 'ID')->where('ID', 1)->get();
        $sql = "SELECT `wp_posts`.`post_title` as name,`ID` FROM `wp_posts` WHERE `ID` = 1";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testSelectWithUppercaseIdColumnNoTicks()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $result = $query->select('wp_posts.post_title as    name', 'ID')->where('ID', 1)->first();
        $compiled = $query->lastCompiledSQL;
        $sql = "SELECT `wp_posts`.`post_title` AS `name`,`ID` FROM `wp_posts` WHERE `ID` = 1 LIMIT 1 OFFSET 0";
        $this->assertTrue( $compiled == $sql);
        $this->assertTrue( !empty($result['name']));
    }

    public function testSelectTable()
    {
        $query = new \TypeRocket\Database\Query('wp_posts');
        $query->setSelectTable('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $compiled = (string) $query->where('ID', 1);
        $sql = "SELECT `wp_posts`.* FROM `wp_posts` WHERE `ID` = 1";
        $this->assertTrue( $compiled == $sql);
    }

    public function testSelectTableOrderBy()
    {
        $query = new \TypeRocket\Database\Query('wp_posts');
        $query->setSelectTable('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $query->orderBy('post_title');
        $compiled = (string) $query->where('ID', 1);
        $sql = "SELECT `wp_posts`.* FROM `wp_posts` WHERE `ID` = 1 ORDER BY `post_title` ASC";
        $this->assertTrue( $compiled == $sql);
    }

    public function testSelectTableReorder()
    {
        $query = new \TypeRocket\Database\Query('wp_posts');
        $query->setSelectTable('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $query->orderBy('post_title');
        $query->reorder('post_content');
        $compiled = (string) $query->where('ID', 1);
        $sql = "SELECT `wp_posts`.* FROM `wp_posts` WHERE `ID` = 1 ORDER BY `post_content` ASC";
        $this->assertTrue( $compiled == $sql);
    }

    public function testSelectTableReorderRemove()
    {
        $query = new \TypeRocket\Database\Query('wp_posts');
        $query->setSelectTable('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $query->orderBy('post_title');
        $query->reorder();
        $compiled = (string) $query->where('ID', 1);
        $sql = "SELECT `wp_posts`.* FROM `wp_posts` WHERE `ID` = 1";
        $this->assertTrue( $compiled == $sql);
    }

    public function testSelectWithTake()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->select('post_title', 'ID')->take(10)->where('ID', 1)->get();
        $sql = "SELECT `post_title`,`ID` FROM `wp_posts` WHERE `ID` = 1 LIMIT 10 OFFSET 0";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testCountWithWhere()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->take(10)->where('ID', 1)->count();
        $sql = "SELECT COUNT(*) FROM `wp_posts` WHERE `ID` = 1 LIMIT 10 OFFSET 0";
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

    public function testSelectWithPagination()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->select('post_title', 'ID');

        $clone = clone $query;
        $first = $query->paginate(25, 1);
        $last = $clone->paginate(25, 10000);

        $this->assertTrue( is_null($last) );
        $this->assertInstanceOf( \TypeRocket\Database\Results::class , $first->getResults() );
    }

    public function testWpTableLookupSelect()
    {
        update_post_meta(1, 'testWpTableLookupSelect', '12345689101112');
        $meta = Query::new('@postmeta')->where('meta_value', '12345689101112');
        $result = $meta->first();
        delete_post_meta(1, 'testWpTableLookupSelect');
        $this->assertTrue( $result['meta_value'] === '12345689101112');
    }

}