<?php
declare(strict_types=1);

namespace Query;

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

}
