<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\WPTermTaxonomy;

class WPTermTest extends TestCase
{
    public function testBasicTermTaxonomiesRelationship()
    {
        $term = new WPTerm();
        $terms = $term->findAll([1])->get();
        foreach ($terms as $term) {
            $taxonomies = $term->termTaxonomies()->first();
            $this->assertTrue( $taxonomies instanceof WPTermTaxonomy);
        }
    }
}