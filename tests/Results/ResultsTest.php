<?php
namespace Results;

class ResultsTest extends \PHPUnit_Framework_TestCase
{

    public function testPrependToResults()
    {
        $results = new \TypeRocket\Database\Results();
        $results->prepend(true);

        $this->assertTrue( $results[0] );
    }

    public function testCastToResultsToModel()
    {
        $results = new \TypeRocket\Database\Results();

        $item1 = ['post_title' => 'New Post 1'];
        $item2 = ['post_title' => 'New Post 2'];

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

}