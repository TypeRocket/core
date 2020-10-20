<?php
declare(strict_types=1);

class TaxonomyTest extends \PHPUnit\Framework\TestCase
{
    public function testPostTypeReg()
    {
        $pt = new \TypeRocket\Register\Taxonomy('Hat');
        $labels = $pt->getArguments()['labels'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($pt->getMaxIdLength() === 32);
        $this->assertTrue($labels['name'] === 'Hats');
        $this->assertTrue($labels['search_items'] === 'Search Hats');
    }

    public function testPostTypeRegPlural()
    {
        $pt = new \TypeRocket\Register\Taxonomy('Hat', 'Pats');
        $labels = $pt->getArguments()['labels'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($labels['name'] === 'Pats');
        $this->assertTrue($labels['search_items'] === 'Search Pats');

    }

    public function testPostTypeRegPluralAsSettings()
    {
        $pt = new \TypeRocket\Register\Taxonomy('Hat', ['description' => 'a desc']);
        $desc = $pt->getArguments()['description'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($desc === 'a desc');
    }

    public function testPostTypeRegWithId()
    {
        $pt = new \TypeRocket\Register\Taxonomy('Hat', 'Hats', null, 'happy');

        $this->assertTrue($pt->getId() === 'happy');
    }

    public function testPostTypeRegExisting()
    {
        $pt = new \TypeRocket\Register\Taxonomy('Gum', 'Gum', null, 'post_tag');

        $this->assertTrue($pt->getId() === 'post_tag');
        $this->assertTrue($pt->getExisting() instanceof \WP_Taxonomy);
    }
}