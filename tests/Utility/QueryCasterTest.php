<?php
namespace TypeRocket\tests\Utility;

use App\Models\Category;
use App\Models\Post;
use PHPUnit\Framework\TestCase;
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

    public function testTermCaster()
    {
        $results = QueryCaster::terms(Category::class, [
            'hide_empty' => false,
        ]);

        $this->assertTrue( $results->first() instanceof Category);
        $this->assertTrue( $results->count() > 0 );
    }
}