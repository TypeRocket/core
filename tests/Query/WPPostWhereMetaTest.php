<?php

namespace Query;

use App\Models\Post;
use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Results;
use TypeRocket\Models\WPPost;

class WPPostWhereMetaTest extends TestCase
{

    public function testPostTypeSelectWhereMetaMulti()
    {
        delete_post_meta(1, 'k1', '1');
        delete_post_meta(1, 'k2', '2');

        update_post_meta(1, 'k1', '1');
        update_post_meta(1, 'k2', '2');

        $post = new WPPost('post');
        $compiled = (string) $post->whereMeta('k1', 1)->whereMeta('k2', 2)->getQuery();
        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta AS tr_mt0 ON `wp_posts`.`ID` = `tr_mt0`.`post_id` INNER JOIN wp_postmeta AS tr_mt1 ON `wp_posts`.`ID` = `tr_mt1`.`post_id` WHERE post_type = \'post\' AND (  `tr_mt0`.`meta_key` = \'k1\' AND `tr_mt0`.`meta_value` = 1 )  AND (  `tr_mt1`.`meta_key` = \'k2\' AND `tr_mt1`.`meta_value` = 2 ) ';
        $the_post = $post->first();
        $this->assertTrue( $the_post instanceof WPPost);
        $this->assertTrue( trim($sql) === trim($compiled));

        delete_post_meta(1, 'k1', '1');
        delete_post_meta(1, 'k2', '2');
    }

    public function testPostTypeSelectWhereMeta()
    {
        delete_post_meta(1, 'k1');
        update_post_meta(1, 'k1', 'Hello testPostTypeSelectWhereMeta');

        $post = new WPPost('post');
        $compiled = (string) $post->whereMeta('k1', 'like', 'Hello%')->getQuery();
        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta AS tr_mt0 ON `wp_posts`.`ID` = `tr_mt0`.`post_id` WHERE post_type = \'post\' AND (  `tr_mt0`.`meta_key` = \'k1\' AND `tr_mt0`.`meta_value` like \'Hello%\' ) ';
        $the_post = $post->first();
        $this->assertTrue( $the_post instanceof WPPost);
        $this->assertTrue( $sql === $compiled);

        delete_post_meta(1, 'k1');
    }

    public function testPostTypeSelectWhereMetaTwice()
    {
        $post = new WPPost('post');
        $compiled = (string) $post->whereMeta('meta_key', 'like', 'Hello%')->whereMeta('meta_key', 'like', 'Hello%', 'OR')->getQuery();
        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta AS tr_mt0 ON `wp_posts`.`ID` = `tr_mt0`.`post_id` INNER JOIN wp_postmeta AS tr_mt1 ON `wp_posts`.`ID` = `tr_mt1`.`post_id` WHERE post_type = \'post\' AND (  `tr_mt0`.`meta_key` = \'meta_key\' AND `tr_mt0`.`meta_value` like \'Hello%\' )  OR (  `tr_mt1`.`meta_key` = \'meta_key\' AND `tr_mt1`.`meta_value` like \'Hello%\' ) ';
        $this->assertTrue( $sql === $compiled);
    }

    public function testPostTypeSelectWhereMetaDefault()
    {
        $post = new WPPost('post');
        $compiled = (string) $post->whereMeta('meta_key')->getQuery();
        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta AS tr_mt0 ON `wp_posts`.`ID` = `tr_mt0`.`post_id` WHERE post_type = \'post\' AND (  `tr_mt0`.`meta_key` = \'meta_key\' AND `tr_mt0`.`meta_value` IS NULL ) ';
        $this->assertTrue( $sql === $compiled);
    }

    public function testPostTypeSelectWhereMetaArrayValue()
    {
        delete_post_meta(1, 'k1');
        delete_post_meta(1, 'k2');

        update_post_meta(1, 'k1', 'Hello testPostTypeSelectWhereMetaArrayValue');
        update_post_meta(1, 'k2', '2');

        $post = new WPPost('post');

        $compiled = (string) $post
            ->whereMeta([
                [
                    'column' => 'k1',
                    'operator' => 'like',
                    'value' => 'Hello%'
                ],
                'AND',
                [
                    'column' => 'k2',
                    'operator' => '!=',
                    'value' => null
                ]
            ])
            ->getQuery();

        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta AS tr_mt0 ON `wp_posts`.`ID` = `tr_mt0`.`post_id` INNER JOIN wp_postmeta AS tr_mt1 ON `wp_posts`.`ID` = `tr_mt1`.`post_id` WHERE post_type = \'post\' AND (  (  `tr_mt0`.`meta_key` = \'k1\' AND `tr_mt0`.`meta_value` like \'Hello%\' )  AND (  `tr_mt1`.`meta_key` = \'k2\' AND `tr_mt1`.`meta_value` IS NOT NULL )  ) ';


        $the_post = $post->first();
        $this->assertTrue( $the_post instanceof WPPost);
        $this->assertTrue( $sql === $compiled);

        delete_post_meta(1, 'k1');
        delete_post_meta(1, 'k2');
    }

    public function testPostTypeSelectWhereMetaArrayValueWithOtherWhere()
    {
        delete_post_meta(1, 'k1');
        delete_post_meta(1, 'k2');

        update_post_meta(1, 'k1', 'Hello testPostTypeSelectWhereMetaArrayValueWithOtherWhere');
        update_post_meta(1, 'k2', '2');

        $post = new WPPost();

        $compiled = (string) $post
            ->whereMeta([
                [
                    'column' => 'meta_key',
                    'operator' => 'like',
                    'value' => 'Hello%'
                ],
                'AND',
                [
                    'column' => 'meta_key',
                    'operator' => '!=',
                    'value' => null
                ]
            ], 'OR')
            ->orWhere('ID', 2)
            ->getQuery();

        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta AS tr_mt0 ON `wp_posts`.`ID` = `tr_mt0`.`post_id` INNER JOIN wp_postmeta AS tr_mt1 ON `wp_posts`.`ID` = `tr_mt1`.`post_id` WHERE post_type = \'post\' AND (  (  `tr_mt0`.`meta_key` = \'meta_key\' AND `tr_mt0`.`meta_value` like \'Hello%\' )  AND (  `tr_mt1`.`meta_key` = \'meta_key\' AND `tr_mt1`.`meta_value` IS NOT NULL )  )  OR ID = 2';

        $num_posts = $post->count();
        $this->assertTrue( $sql === $compiled);
        $this->assertTrue( $num_posts == 2);

        delete_post_meta(1, 'k1');
        delete_post_meta(1, 'k2');
    }

    public function testPostTypeSelectWhereMetaArrayValueWithOtherWhereIsNull()
    {
        $compiled = (string) (new WPPost('post'))
            ->orWhere('ID', 2)
            ->whereMeta([
                [
                    'column' => 'meta_key',
                    'operator' => 'like',
                    'value' => 'Hello%'
                ],
                'AND',
                [
                    'column' => 'meta_key',
                    'operator' => '=',
                    'value' => null
                ]
            ], 'OR')
            ->getQuery();

        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta AS tr_mt0 ON `wp_posts`.`ID` = `tr_mt0`.`post_id` INNER JOIN wp_postmeta AS tr_mt1 ON `wp_posts`.`ID` = `tr_mt1`.`post_id` WHERE post_type = \'post\' OR ID = 2 AND (  (  `tr_mt0`.`meta_key` = \'meta_key\' AND `tr_mt0`.`meta_value` like \'Hello%\' )  AND (  `tr_mt1`.`meta_key` = \'meta_key\' AND `tr_mt1`.`meta_value` IS NULL )  ) ';

        $this->assertTrue( $sql === $compiled);
    }
}