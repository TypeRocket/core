<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;

class JoinTest extends TestCase
{
    public function testBasicJoin()
    {
        $query = new Query();
        $query->table('wp_posts');
        $query->setIdColumn('ID');
        $query->select('wp_posts.post_title', 'wp_posts.ID', 'wp_postmeta.meta_key')
            ->distinct()
            ->join('wp_postmeta', 'wp_postmeta.post_id', 'wp_posts.ID')
            ->where('wp_posts.ID', 1)
            ->get();
        $compiled = $query->lastCompiledSQL;
        $sql = "SELECT DISTINCT `wp_posts`.`post_title`,`wp_posts`.`ID`,`wp_postmeta`.`meta_key` FROM `wp_posts` INNER JOIN `wp_postmeta` ON `wp_postmeta`.`post_id` = `wp_posts`.`ID` WHERE `wp_posts`.`ID` = 1";
        $this->assertTrue( $compiled == $sql);
    }

    public function testJoinSubQuery()
    {
        $query = new Query('wp_posts', null, 'ID');

        $query_nested = (new Query('wp_posts', null, 'ID'))->setJoinAs('wpp');

        $compiled = (string) $query->select('wp_posts.post_title', 'wp_posts.ID')
            ->distinct()
            ->join($query_nested, 'wpp.ID', 'wp_posts.ID')
            ->where('wp_posts.ID', 1);

        $sql = "SELECT DISTINCT `wp_posts`.`post_title`,`wp_posts`.`ID` FROM `wp_posts` INNER JOIN ( SELECT * FROM `wp_posts` ) `wpp` ON `wpp`.`ID` = `wp_posts`.`ID` WHERE `wp_posts`.`ID` = 1";
        $this->assertTrue( $compiled == $sql);
    }

    public function testSelectTableJoinWhereUnique()
    {
        $query = new Query('wp_posts', true, 'ID');
        $compiled = (string) $query
            ->where('ID', 1)
            ->where([
                [   // index name based lookup
                    'value' => 'meta_key',
                    'operator' => '=',
                    'column' => 'meta_key',
                ],
                'AND',
                [   // index based lookup
                    'column' => 'meta_value',
                    'operator' => 'like',
                    'value' => 'Hello%',
                ]
            ])
            ->distinct()
            ->join('wp_postmeta', 'wp_posts.ID', 'wp_postmeta.post_id') // this duplicate should be removed when compiled
            ->join('wp_postmeta', 'wp_posts.ID', 'wp_postmeta.post_id');
        $sql = "SELECT DISTINCT `wp_posts`.* FROM `wp_posts` INNER JOIN `wp_postmeta` ON `wp_posts`.`ID` = `wp_postmeta`.`post_id` WHERE `ID` = 1 AND (  `meta_key` = 'meta_key' AND `meta_value` like 'Hello%' ) ";
        $this->assertTrue( $compiled == $sql);
    }

    public function testJoinSubQueryUnique()
    {
        $query = new Query('wp_posts', null, 'ID');

        $query_nested = (new Query('wp_posts', null, 'ID'))->setJoinAs('wpp');

        $compiled = (string) $query->select('wp_posts.post_title', 'wp_posts.ID')
            ->distinct()
            ->join($query_nested, 'wpp.ID', 'wp_posts.ID')
            ->join($query_nested, 'wpp.ID', 'wp_posts.ID')
            ->where('wp_posts.ID', 1);

        $sql = "SELECT DISTINCT `wp_posts`.`post_title`,`wp_posts`.`ID` FROM `wp_posts` INNER JOIN ( SELECT * FROM `wp_posts` ) `wpp` ON `wpp`.`ID` = `wp_posts`.`ID` WHERE `wp_posts`.`ID` = 1";

        $this->assertTrue( $compiled == $sql);
    }
}