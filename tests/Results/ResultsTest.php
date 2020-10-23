<?php
declare(strict_types=1);

namespace Results;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\ResultsPaged;
use TypeRocket\Database\ResultsPostMeta;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPOption;
use TypeRocket\Models\WPPost;

class ResultsTest extends TestCase
{

    public function testPrependToResults()
    {
        $results = new \TypeRocket\Database\Results();
        $results->prepend(true);

        $this->assertTrue( $results[0] );
    }

    public function testArrayResults()
    {
        $results = new \TypeRocket\Database\Results([
            'tr' => ['ID' => 0]
        ]);

        $results->castResults(WPPost::class);

        $this->assertTrue( $results['tr'] instanceof WPPost);
    }

    public function testCastToResultsToModel()
    {
        $results = new \TypeRocket\Database\Results();

        $item1 = ['post_title' => 'New Post 1', 'fillable' => 'yes'];
        $item2 = ['post_title' => 'New Post 2', 'fillable' => 'yes'];

        $results->append($item1);
        $results->append($item2);
        $results->class = \TypeRocket\Models\WPPost::class;
        $results->castResults();
        $object1 = $results[0];
        $object2 = $results[1];

        $r1 = $object1 instanceof \TypeRocket\Models\WPPost;
        $r2 = $object2 instanceof \TypeRocket\Models\WPPost;

        $title1 = $object1->post_title;
        $title2 = $object2->post_title;

        $this->assertTrue( $r1 );
        $this->assertTrue( $r2 );
        $this->assertTrue( $title1 == $item1['post_title'] );
        $this->assertTrue( $title2 == $item2['post_title'] );
        $this->assertTrue( $object2->fillable == $item2['fillable'] );
    }

    public function testCastResultsReturnNull()
    {
        $results = new \TypeRocket\Database\Results();
        $isNull = $results->castResults();
        $this->assertTrue( $isNull === null );
    }

    public function testCastResultsReturnNoneModel()
    {
        $results = new \TypeRocket\Database\Results();
        $results->prepend(['post_title' => 'New Post 1']);
        $results->class = \stdClass::class;
        $results->castResults();
        $this->assertTrue( $results[0] instanceof \stdClass );
    }

    public function testCastResultsReturnNoneModelWithCustomProperty()
    {
        $results = new \TypeRocket\Database\Results();
        $results->prepend(['post_title' => 'New Post 1']);
        $results->class = \stdClass::class;
        $results->property = 'attributes';
        $results->castResults();
        $this->assertTrue( $results[0] instanceof \stdClass );
        $this->assertTrue( isset($results[0]->attributes) );
    }

    public function testAutoModelResults()
    {
        $posts = (new WPPost())->findAll()->where('post_status', 'publish')->get();

        foreach ($posts as $post ) {
            $this->assertTrue( $post instanceof WPPost );
        }
    }

    public function testResultsPagedIterator()
    {

        $posts = (new WPPost())->findAll()->published()->paginate(25, 1);

        foreach ($posts as $post) {
            $this->assertTrue( $post instanceof WPPost );
        }
    }

    public function testResultsPostMeta()
    {
        add_post_meta(1, 'tr_test_meta', true, true);

        $posts = (new WPPost('post'))->with('meta')->findAll([1])->get();

        delete_post_meta(1, 'tr_test_meta');

        foreach ($posts as $post) {
            $post->meta->initKeyStore();
            $this->assertTrue( $post->meta instanceof ResultsPostMeta );
        }
    }

    public function testResultsPages()
    {
        /** @var ResultsPaged $options */
        $options = (new WPOption)->findAll()->paginate();

        $array = $options->toArray();

        $this->assertTrue($options instanceof ResultsPaged);
        $this->assertTrue(is_array($array));
        $this->assertTrue($array['links']['next'] === 'http://example.com/php-unit-tests?phpunit=yes&paged=2');
        $this->assertTrue($array['links']['previous'] === null);
        $this->assertStringContainsString('http://example.com/php-unit-tests?phpunit=yes&paged=', $array['links']['last']);
        $this->assertTrue($array['links']['first'] === 'http://example.com/php-unit-tests?phpunit=yes&paged=1');
    }

    public function testResultsPagesNull()
    {
        /** @var ResultsPaged $posts */
        $posts = (new WPPost)->with('meta')->findAll([1,2])->paginate();

        $array = $posts->toArray();

        $this->assertTrue($posts instanceof ResultsPaged);
        $this->assertTrue(is_array($array));
        $this->assertTrue($array['links']['next'] === null);
        $this->assertTrue($array['links']['previous'] === null);
        $this->assertTrue($array['links']['last'] === null);
        $this->assertTrue($array['links']['first'] === null);
    }

    public function testResultsEagerLoading()
    {
        add_post_meta(1, 'tr_test_meta', true, true);

        $posts = (new WPPost('post'))->findAll([1])->get()->load('meta');

        delete_post_meta(1, 'tr_test_meta');

        /** @var Model $post */
        foreach ($posts as $post) {
            $meta = $post->getRelationship('meta');
            $meta->initKeyStore();
            $this->assertTrue( $post->meta instanceof ResultsPostMeta );
        }
    }

}