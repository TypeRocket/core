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
        $term = $term->findById(1);
        $taxonomies = $term->termTaxonomies()->first();
        $this->assertTrue( $taxonomies instanceof WPTermTaxonomy);
    }
}