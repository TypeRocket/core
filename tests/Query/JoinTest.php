<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;

class JoinTest extends TestCase
{
    public function testBasicJoin()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->setIdColumn('ID');
        $query->select('wp_posts.post_title', 'wp_posts.ID', 'wp_postmeta.meta_key')
            ->distinct()
            ->join('wp_postmeta', 'wp_postmeta.post_id', 'wp_posts.ID')
            ->where('wp_posts.ID', 1)
            ->get();
        $sql = "SELECT DISTINCT wp_posts.post_title,wp_posts.ID,wp_postmeta.meta_key FROM wp_posts INNER JOIN wp_postmeta ON wp_postmeta.post_id = wp_posts.ID WHERE wp_posts.ID = 1";
        $this->assertTrue( $query->lastCompiledSQL == $sql);
    }
}