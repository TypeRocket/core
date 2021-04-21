<?php
declare(strict_types=1);

use TypeRocket\Register\PostType;
use TypeRocket\Register\Registry;

class PostTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testPostTypeReg()
    {
        $pt = new \TypeRocket\Register\PostType('Hat');
        $labels = $pt->getArguments()['labels'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($pt->getMaxIdLength() === 20);
        $this->assertTrue($labels['name'] === 'Hats');
        $this->assertTrue($labels['view_items'] === 'View Hats');
    }

    public function testPostTypeRegModelResourceNull()
    {
        $pt = new \TypeRocket\Register\PostType('Hat');
        $pt->register();

        $reg = Registry::getPostTypeResource('hat');
        $model = $reg['object']->getResource('model');

        $this->assertTrue($model === null);
    }

    public function testPostTypeRegModelResource()
    {
        $pt = new \TypeRocket\Register\PostType('Hat');
        $pt->setModelClass(\TypeRocket\Models\WPUser::class);
        $pt->register();

        $reg = Registry::getPostTypeResource('hat');
        $model = $reg['object']->getResource('model');

        $this->assertTrue($model === \TypeRocket\Models\WPUser::class);
    }

    public function testPostTypeRegPlural()
    {
        $pt = new \TypeRocket\Register\PostType('Hat', 'Pats');
        $labels = $pt->getArguments()['labels'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($labels['name'] === 'Pats');
        $this->assertTrue($labels['view_items'] === 'View Pats');

    }

    public function testPostTypeRegPluralAsSettings()
    {
        $pt = new \TypeRocket\Register\PostType('Hat', ['description' => 'a desc']);
        $desc = $pt->getArguments()['description'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($desc === 'a desc');
    }

    public function testPostTypeRegWithId()
    {
        $pt = new \TypeRocket\Register\PostType('Hat', 'Hats', null, 'happy');

        $this->assertTrue($pt->getId() === 'happy');
    }

    public function testPostTypeRegExisting()
    {
        $pt = new \TypeRocket\Register\PostType('Art', 'Arts', null, 'post');

        $this->assertTrue($pt->getId() === 'post');
        $this->assertTrue($pt->getExisting() instanceof \WP_Post_Type);
    }
}