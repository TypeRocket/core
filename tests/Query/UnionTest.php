<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;

class UnionTest extends TestCase
{
    public function testBasicJoinUnion()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID'; // uppercase
        $query->select('wp_posts.post_title', 'wp_posts.ID', 'wp_postmeta.meta_key')
              ->distinct()
              ->join('wp_postmeta', 'wp_postmeta.post_id', 'wp_posts.ID')
              ->where('wp_posts.ID', 1);

        $query2 = new \TypeRocket\Database\Query();
        $query2->table('wp_posts');
        $query2->idColumn = 'ID'; // uppercase
        $query2->select('wp_posts.post_title', 'wp_posts.ID', 'wp_postmeta.meta_key')
              ->distinct()
              ->join('wp_postmeta', 'wp_postmeta.post_id', 'wp_posts.ID')
              ->where('wp_posts.ID', 2)
              ->union($query)
              ->get();

        $sql = "SELECT DISTINCT `wp_posts`.`post_title`,`wp_posts`.`ID`,`wp_postmeta`.`meta_key` FROM `wp_posts` INNER JOIN `wp_postmeta` ON `wp_postmeta`.`post_id` = `wp_posts`.`ID` WHERE `wp_posts`.`ID` = 2 UNION SELECT DISTINCT `wp_posts`.`post_title`,`wp_posts`.`ID`,`wp_postmeta`.`meta_key` FROM `wp_posts` INNER JOIN `wp_postmeta` ON `wp_postmeta`.`post_id` = `wp_posts`.`ID` WHERE `wp_posts`.`ID` = 1";
        $this->assertTrue( $query2->lastCompiledSQL == $sql);
    }

    public function testSimpleUnion()
    {
        $first = \TypeRocket\Database\Query::new()->table('wp_posts')->setIdColumn('ID');
        $first->select('post_title', 'ID')
              ->where('ID', 1);

        $last = \TypeRocket\Database\Query::new()->table('wp_posts')->setIdColumn('ID');
        $last->select('post_title', 'ID')
             ->where('ID', 2)
             ->union($first) // union
             ->get();

        $sql = "SELECT `post_title`,`ID` FROM `wp_posts` WHERE `ID` = 2 UNION SELECT `post_title`,`ID` FROM `wp_posts` WHERE `ID` = 1";
        $this->assertTrue( $last->lastCompiledSQL == $sql);
    }
}