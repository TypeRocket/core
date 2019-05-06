<?php
declare(strict_types=1);

namespace Query;


use PHPUnit\Framework\TestCase;
use TypeRocket\Models\Meta\WPPostMeta;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPUser;

class BelongsToTest extends TestCase
{
    public function testBelongsTo()
    {
        $meta = new WPPostMeta();
        $post = $meta->findById(1)->post();
        $sql = $post->getSuspectSQL();
        $expected = "SELECT * FROM wp_posts WHERE post_type = 'post' AND ID = '2' LIMIT 1 OFFSET 0";
        $rel = $post->getRelatedModel();
        $this->assertTrue( $rel instanceof WPPostMeta );
        $this->assertTrue($sql == $expected);
    }

    public function testBelongsEagerLoad()
    {
        $post = new WPPost();
        $result = $post->with('author')->findById(1);
        $author = $result->getRelationship('author');

        $this->assertTrue( $author instanceof WPUser );
    }
}