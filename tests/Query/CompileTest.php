<?php

namespace Query;

class CompileTest extends \PHPUnit_Framework_TestCase
{
    public function testUpdatePostTitle()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table = 'wp_posts';
        $query->idColumn = 'ID';
        $query->run = false;
        $query->where('ID', 1)->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `wp_posts`.`post_title`='My Title' WHERE `wp_posts`.`ID` = '1'";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testCreatePost()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table = 'wp_posts';
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
        $sql = "INSERT INTO `wp_posts` (`wp_posts`.`post_title`,`wp_posts`.`post_content`,`wp_posts`.`post_content_filtered`,`wp_posts`.`post_mime_type`,`wp_posts`.`post_excerpt`,`wp_posts`.`post_name`,`wp_posts`.`guid`,`wp_posts`.`post_password`,`wp_posts`.`to_ping`,`wp_posts`.`pinged`,`wp_posts`.`post_date`,`wp_posts`.`post_modified`,`wp_posts`.`post_date_gmt`,`wp_posts`.`post_modified_gmt`)  VALUES  ( 'My Title','My content.','','','My...','my-name','','','','','{$time}','{$time}','{$time}','{$time}' ) ";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testDeletePost()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table = 'wp_posts';
        $query->idColumn = 'ID';
        $query->run = false;
        $query->where('ID', 1)->delete();
        $sql = "DELETE FROM `wp_posts` WHERE `wp_posts`.`ID` = '1'";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }

    public function testCountPosts()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table = 'wp_posts';
        $query->idColumn = 'ID';
        $result = $query->count();
        $sql = "SELECT COUNT(*) FROM `wp_posts`";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }
}