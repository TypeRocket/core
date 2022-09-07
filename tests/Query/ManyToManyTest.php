<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;

class CategoryMockClassTest extends WPTerm
{
    public const TAXONOMY = 'category';
}

class PostMockClassTest extends WPPost
{
    public const POST_TYPE = 'post';

    public function terms() {
        return $this->belongsToMany( WPTerm::class, 'posts_terms' );
    }
}

class ManyToManyTest extends TestCase
{

    public function testManyToMany()
    {
        $post = new WPPost();
        $terms = $post->findById(1)->belongsToMany( WPTerm::class, 'posts_terms' );
        $sql = $terms->getSuspectSQL();
        $expected = "SELECT DISTINCT `wp_terms`.* FROM `wp_terms` INNER JOIN `posts_terms` ON `posts_terms`.`terms_id` = `wp_terms`.`term_id` WHERE `posts_terms`.`posts_id` = '1'";
        $this->assertTrue( $terms->getRelatedModel() instanceof WPPost );
        $junction = $terms->getJunction();
        $this->assertTrue( $junction['table'] == 'posts_terms' );
        $this->assertTrue($sql == $expected);
    }

    public function testManyToManyCategory()
    {
        $post = new WPPost();
        $terms = $post->findById(1)->belongsToMany( CategoryMockClassTest::class, 'posts_terms' );
        $sql = $terms->getSuspectSQL();
        $expected = "SELECT DISTINCT `wp_terms`.* FROM `wp_terms` INNER JOIN `wp_term_taxonomy` ON `wp_term_taxonomy`.`term_id` = `wp_terms`.`term_id` INNER JOIN `posts_terms` ON `posts_terms`.`terms_id` = `wp_terms`.`term_id` WHERE `wp_term_taxonomy`.`taxonomy` = 'category' AND `posts_terms`.`posts_id` = '1'";
        $this->assertTrue( $terms->getRelatedModel() instanceof WPPost );
        $junction = $terms->getJunction();
        $this->assertStringContainsString( $junction['table'], 'posts_terms' );
        $this->assertStringContainsString($sql, $expected);
    }

    public function testJunctionAttach()
    {
        $post = new WPPost();
        $terms = $post->findById(1)->belongsToMany( WPTerm::class, 'posts_terms' );
        $result = $terms->attach( [1,2,3] );
        $expected = "INSERT INTO `posts_terms` (`terms_id`,`posts_id`)  VALUES  ( 1,'1' ) , ( 2,'1' ) , ( 3,'1' ) ";
        $sql = $result[1]->lastCompiledSQL;
        $this->assertTrue($sql == $expected);
    }

    public function testJunctionGetEager()
    {
        $post = new PostMockClassTest();
        /** @var PostMockClassTest $posts */
        $posts = $post->with('terms')->findById(1);
        $terms = $posts->getRelationship('terms');
        $this->assertTrue(!empty($terms));
    }

    public function testJunctionDetachList()
    {
        $post = new WPPost();
        $terms = $post->findById(1)->belongsToMany( WPTerm::class, 'posts_terms' );
        $result = $terms->detach( [1,2,3] );
        $expected = "DELETE FROM `posts_terms` WHERE `posts_id` = '1' AND `terms_id` IN (1,2,3)";
        $sql = $result[1]->lastCompiledSQL;
        $this->assertTrue($sql == $expected);
    }

    public function testJunctionDetachAll()
    {
        $post = new WPPost();
        $terms = $post->findById(1)->belongsToMany( WPTerm::class, 'posts_terms' );
        $result = $terms->detach();
        $expected = "DELETE FROM `posts_terms` WHERE `posts_id` = '1'";
        $sql = $result[1]->lastCompiledSQL;
        $this->assertTrue($sql == $expected);
    }

    public function testJunctionSync()
    {
        $post = new WPPost();
        $terms = $post->findById(1)->belongsToMany( WPTerm::class, 'posts_terms' );
        $terms->sync(['1']);
        $term_list = $terms->get();
        foreach ( $term_list as $term ) {
            $this->assertTrue( $term instanceof WPTerm );
        }
        $terms->detach();
    }

}