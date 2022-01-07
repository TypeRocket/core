<?php
declare(strict_types=1);

namespace Query;

use App\Models\Category;
use App\Models\Post;
use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Results;
use TypeRocket\Models\WPPost;

class WPPostTest extends TestCase
{
    public function testBasicPostTypeSelect()
    {
        $post = new WPPost('post');
        $posts = $post->published()->get();
        $this->assertTrue( $posts instanceof Results);
    }

    public function testPostHasCategory()
    {
        $post = new Post();
        $result = $post->published()->has('categories')->find(1);

        $this->assertTrue( $result instanceof Post);
    }

    public function testPostsHaveCategory()
    {
        $post = new Post();
        $id = wp_insert_post([
            'post_title' => 'tr_posts_term_test',
            'post_content' => 'tr_posts_term_test',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_category' => array( 1 )
        ]);

        $result = $post->published()->has('categories')->get();

        wp_delete_post($id, true);
        $this->assertTrue( $result instanceof Results);
        $this->assertTrue( $result->count() === 2);
    }

}
