<?php
declare(strict_types=1);

namespace Query;

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
        $meta = $post->findById(1)->meta();
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

}