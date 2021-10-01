<?php
declare(strict_types=1);

namespace Query;

use App\Models\Category;
use App\Models\Post;
use PHPUnit\Framework\TestCase;
use TypeRocket\Models\Meta\WPPostMeta;
use TypeRocket\Models\WPUser;

class PostTest extends TestCase
{

    public function testCreateWithSlashing()
    {
        $post = new \TypeRocket\Models\WPPost();

        $data = [
            'post_title' => 'About \TypeRocket\Name Code',
            'post_name' => 'about-the-code',
            'post_excerpt' => 'About \TypeRocket\Name Code',
            'post_content' => 'Content for \\ the "main" \TypeRocket\Name \'not that much\' and that\'s it.',
        ];

        $post->create($data);

        wp_delete_post($post->getID(), true);

        $content = $post->getProperty('post_content');
        $title = $post->getProperty('post_title');
        $excerpt = $post->getProperty('post_excerpt');

        $this->assertTrue($content == $data['post_content']);
        $this->assertTrue($title == $data['post_title']);
        $this->assertTrue($excerpt == $data['post_excerpt']);
    }

    public function testUpdateWithSlashing()
    {
        $post = new \TypeRocket\Models\WPPost();
        $post->findById(1);

        $data = [
            'post_title' => 'Hello Update \TypeRocket\Name Code',
            'post_name' => 'about-the-code',
            'post_excerpt' => 'Update \TypeRocket\Name Code',
            'post_content' => 'Welcome Updated for the "main" \TypeRocket\Name \'not that much\' and that\'s it all.',
        ];

        $post->update($data);

        $content = $post->getProperty('post_content');
        $title = $post->getProperty('post_title');
        $excerpt = $post->getProperty('post_excerpt');

        $this->assertTrue($content == $data['post_content']);
        $this->assertTrue($title == $data['post_title']);
        $this->assertTrue($excerpt == $data['post_excerpt']);
    }

    public function testRelationshipPostMeta()
    {
        $post = new \TypeRocket\Models\WPPost();
        $meta = $post->findById(1)->meta(true);
        $results = $meta->get();

        foreach ($results as $result ) {
            $this->assertTrue( $result instanceof WPPostMeta);
        }

        $this->assertTrue( $meta instanceof WPPostMeta);
    }

    public function testRelationshipUser()
    {
        $post = new \TypeRocket\Models\WPPost();
        $user = $post->findById(1)->author();
        $user->get();
        $this->assertTrue( $user instanceof WPUser);
    }

    public function testNotExisting()
    {
        $post = new \TypeRocket\Models\WPPost();
        $result = $post->findById(9999);

        $this->assertTrue( $result === null );
    }

    public function testPostTagsAndCategories()
    {
        $sql = (string) Post::new()->tags()->getQuery();
        $expected = "SELECT DISTINCT `wp_terms`.*,`wp_term_taxonomy`.`taxonomy`,`wp_term_taxonomy`.`term_taxonomy_id`,`wp_term_taxonomy`.`description` FROM `wp_terms` INNER JOIN `wp_term_taxonomy` ON `wp_term_taxonomy`.`term_id` = `wp_terms`.`term_id` INNER JOIN `wp_term_relationships` ON `wp_term_relationships`.`term_taxonomy_id` = `wp_term_taxonomy`.`term_taxonomy_id` WHERE `wp_term_taxonomy`.`taxonomy` = 'post_tag' AND `wp_term_taxonomy`.`taxonomy` = 'post_tag' AND `wp_term_relationships`.`object_id` IS NULL";

        $this->assertStringContainsString($sql, $expected);
        $count = Post::new()->find(1)->categories()->get()->count();
        $this->assertTrue($count === 1);
    }

    public function testTagsAndCategoriesPosts()
    {
        $sql = (string) Category::new()->find(1)->posts()->getQuery();
        $expected = "SELECT DISTINCT `wp_posts`.* FROM `wp_posts` INNER JOIN `wp_term_relationships` ON `wp_term_relationships`.`object_id` = `wp_posts`.`ID` WHERE `post_type` = 'post' AND `wp_term_relationships`.`term_taxonomy_id` = '1'";

        $this->assertStringContainsString($sql, $expected);
        $count = Category::new()->find(1)->posts()->get()->count();
        $this->assertTrue($count === 1);
    }
}