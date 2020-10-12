<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;

class CompileTest extends TestCase
{
    public function testUpdatePostTitle()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query->where('ID', 1)->update(['post_title' => 'My Title']);
        $sql = "UPDATE wp_posts SET post_title='My Title' WHERE ID = 1";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testCreatePost()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $time = $query->getDateTime();
        $query->create([
            'post_title' => 'My Title',
            'post_content' => 'My content.',
            'post_content_filtered' => '',
            'post_mime_type' => '',
            'post_excerpt' => 'My...',
            'post_name' => 'my-name',
            'guid' => '',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_date' => $time,
            'post_modified' => $time,
            'post_date_gmt' => $time,
            'post_modified_gmt' => $time,
        ]);
        $sql = "INSERT INTO wp_posts (post_title,post_content,post_content_filtered,post_mime_type,post_excerpt,post_name,guid,post_password,to_ping,pinged,post_date,post_modified,post_date_gmt,post_modified_gmt)  VALUES  ( 'My Title','My content.','','','My...','my-name','','','','','{$time}','{$time}','{$time}','{$time}' ) ";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testDeletePost()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query->where('ID', 1)->delete();
        $sql = "DELETE FROM wp_posts WHERE ID = 1";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testOrderByDescPosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->orderBy('ID', 'DESC')->get();
        $sql = "SELECT * FROM wp_posts ORDER BY ID DESC";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testOrderByAscPosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->orderBy('ID')->get();
        $sql = "SELECT * FROM wp_posts ORDER BY ID ASC";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testOrderByMultiplePosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $result = $query->orderBy('post_content')->orderBy('post_title', 'DESC')->get();
        $sql = "SELECT * FROM wp_posts ORDER BY post_content ASC , post_title DESC";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testOrderByMultiplePostsWithAs()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts')->setTableAs('p');
        $query->idColumn = 'ID';
        $result = (string) $query->orderBy('post_content')->orderBy('post_title', 'DESC');
                                        // extra space
        $sql = "SELECT * FROM wp_posts AS p  ORDER BY post_content ASC , post_title DESC";
        $this->assertTrue( $result == $sql);
    }

    public function testCountPosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $result = $query->count();
        $sql = "SELECT COUNT(*) FROM wp_posts";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
        $this->assertTrue( $result > 0 );
    }

    public function testMaxPosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $result = $query->max('ID');
        $sql = "SELECT MAX(ID) FROM wp_posts";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testMinPosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $result = $query->min('ID');
        $sql = "SELECT MIN(ID) FROM wp_posts";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testSumPosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $result = $query->sum('ID');
        $sql = "SELECT SUM(ID) FROM wp_posts";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testAvgPosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $result = $query->avg('ID');
        $sql = "SELECT AVG(ID) FROM wp_posts";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
        $this->assertTrue( $result > 0 );
    }
}