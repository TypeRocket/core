<?php

namespace Query;


use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;

class ManyToManyTest extends \PHPUnit_Framework_TestCase
{

    public function testManyToMany()
    {
        $post = new WPPost();
        $terms = $post->findById(1)->belongsToMany( WPTerm::class, 'posts_terms' );
        $sql = $terms->getSuspectSQL();
        $expected = "SELECT wp_terms.* FROM wp_terms INNER JOIN posts_terms ON posts_terms.terms_id = wp_terms.term_id WHERE posts_terms.posts_id = '1'";
        $this->assertTrue( $terms->getRelatedModel() instanceof WPPost );
        $this->assertTrue($sql == $expected);
    }

}