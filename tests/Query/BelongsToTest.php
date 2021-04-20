<?php
declare(strict_types=1);

namespace Query;


use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;
use TypeRocket\Database\ResultsMeta;
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
        $expected = "SELECT * FROM `wp_posts` WHERE `ID` = '2' LIMIT 1 OFFSET 0";
        $rel = $post->getRelatedModel();
        $this->assertTrue( $rel instanceof WPPostMeta );
        $this->assertTrue($sql == $expected);
    }

    public function testBelongsEagerLoad()
    {
        $post = new WPPost();

        $numRun = Query::$numberQueriesRun;

        $result = $post->with(['author.meta', 'meta.post'])->findAll([1,2,3])->get();

        foreach ($result as $item) {
            $this->assertTrue( $item->author instanceof WPUser );
            $this->assertTrue( $item->getRelationship('author') instanceof WPUser );
            $this->assertTrue( $item->author->meta instanceof ResultsMeta);
            $this->assertTrue( $item->meta instanceof ResultsMeta);

            foreach ($item->meta as $meta) {
                $this->assertTrue( $meta->post instanceof WPPost);
            }
        }

        $numRun = Query::$numberQueriesRun - $numRun;

        $this->assertTrue( $numRun === 5 );
    }
}