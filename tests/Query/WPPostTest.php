<?php

namespace Query;

use TypeRocket\Database\Results;
use TypeRocket\Models\WPPost;

class WPPostTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicPostTypeSelect()
    {
        $post = new WPPost();
        $posts = $post->published()->get();
        $this->assertTrue( $posts instanceof Results);
    }
}
