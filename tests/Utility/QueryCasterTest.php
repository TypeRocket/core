<?php
namespace TypeRocket\tests\Utility;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use TypeRocket\Models\WPTerm;
use TypeRocket\Utility\QueryCaster;

class QueryCasterTest extends TestCase
{
    public function testPostCaster()
    {
        $results = QueryCaster::posts(Post::class, [
           'post_status' => 'publish'
        ]);

        $this->assertTrue( $results->first() instanceof Post);
        $this->assertTrue( $results->count() > 0 );
    }

    public function testPostCasterFromModel()
    {
        $posts = new \WP_Query([
            'post_status' => 'publish'
        ]);

        $results = Post::castArrayToModelResults($posts->posts);

        $this->assertTrue( $results->first() instanceof Post);
        $this->assertTrue( $results->count() > 0 );
    }

    public function testTermCaster()
    {
        $results = QueryCaster::terms(Category::class, [
            'hide_empty' => false,
        ]);

        $this->assertTrue( $results->first() instanceof Category);
        $this->assertTrue( $results->count() > 0 );
    }

    public function testTermMetaSelectNullCaster()
    {
        $terms = get_terms([
            'taxonomy' => Category::TAXONOMY
        ]);

        $results = QueryCaster::terms(Category::class, [
            'meta_query' => [
                [
                    'key'       => 'select_fund',
                    'value'     => '359',
                ]
            ]
        ]);

        $this->assertTrue( $results->count() === 0 );
    }

    public function testTermMetaSelectCaster()
    {
        /** @var \WP_Term[] $terms */
        $terms = get_terms([
            'taxonomy' => Category::TAXONOMY
        ]);

        foreach ($terms as $term) {
            update_term_meta($term->term_id, 'select_fund', '359');
        }

        $results = QueryCaster::terms(Category::class, [
            'meta_query' => [
                [
                    'key'       => 'select_fund',
                    'value'     => '359',
                ]
            ]
        ]);

        foreach ($terms as $term) {
            delete_term_meta($term->term_id, 'select_fund');
        }

        $this->assertTrue( $results->count() === count($terms) );
    }

    public function testUserCaster()
    {
        $results = QueryCaster::users(User::class, [
            'role' => 'administrator',
        ]);

        $this->assertTrue( $results->first() instanceof User);
        $this->assertTrue( $results->count() > 0 );
    }

    public function testUserNoneCaster()
    {
        $results = QueryCaster::users(User::class, [
            'role' => 'subscriber',
        ]);

        $this->assertTrue( $results->count() === 0 );
    }

    public function testCommentCaster()
    {
        $results = QueryCaster::comments(Comment::class, [
            'post_id' => 1,
        ]);

        $this->assertTrue( $results->first() instanceof Comment);
        $this->assertTrue( $results->count() > 0 );
    }

    public function testCommentNoneCaster()
    {
        $results = QueryCaster::comments(Comment::class, [
            'post_id' => 100,
        ]);

        $this->assertTrue( $results->count() === 0 );
    }
}