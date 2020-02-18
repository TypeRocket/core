<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Results;
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

    public function testPostTypeSelectWhereMeta()
    {
        $term = new WPTerm('category');
        $compiled = (string) $term->whereMeta('meta_key', 'like', 'Hello%')->getQuery();
        $sql = 'SELECT DISTINCT wp_terms.*,wp_term_taxonomy.taxonomy,wp_term_taxonomy.term_taxonomy_id,wp_term_taxonomy.description FROM wp_terms INNER JOIN wp_term_taxonomy ON wp_term_taxonomy.term_id = wp_terms.term_id INNER JOIN wp_termmeta AS tr_mt0 ON `wp_terms`.`term_id` = `tr_mt0`.`term_id` WHERE wp_term_taxonomy.taxonomy = \'category\' AND (  `tr_mt0`.`meta_key` = \'meta_key\' AND `tr_mt0`.`meta_value` like \'Hello%\' ) ';
        $terms = $term->get();
        $this->assertTrue( $terms instanceof Results);
        $this->assertTrue( $sql === $compiled);
    }
}