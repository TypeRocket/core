<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use TypeRocket\Controllers\WPPostController;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Models\AuthUser;
use TypeRocket\Models\WPUser;
use TypeRocket\Register\PostType;
use TypeRocket\Register\Registry;
use \TypeRocket\Models\WPPost;

class CarModel extends WPPost
{
    public const POST_TYPE = 'car';
}

class CarPostTypeController extends WPPostController
{
    protected $modelClass = CarModel::class;

    public function create(Request $request, Response $response, AuthUser $user) {
        // Just to let me know that this has been called
        die('called');
    }

    public function onActionSave($type, CarModel $car, Request $request) {
        // Just to let me know that this has been called
        die('action');
    }
}

class PostTypeTest extends TestCase
{
    public function testPostTypeReg()
    {
        $pt = new PostType('Hat');
        $labels = $pt->getArguments()['labels'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($pt->getMaxIdLength() === 20);
        $this->assertTrue($labels['name'] === 'Hats');
        $this->assertTrue($labels['view_items'] === 'View Hats');
    }

    public function testPostTypeRegModelResourceNull()
    {
        $pt = new PostType('Hat');
        $pt->register();

        $reg = Registry::getPostTypeResource('hat');
        $model = $reg['object']->getResource('model');

        $this->assertTrue($model === null);
    }

    public function testPostTypeRegModelResource()
    {
        $pt = new PostType('Hat');
        $pt->setModelClass(WPUser::class);
        $pt->register();

        $reg = Registry::getPostTypeResource('hat');
        $model = $reg['object']->getResource('model');

        $this->assertTrue($model === WPUser::class);
    }

    public function testPostTypeRegPlural()
    {
        $pt = new PostType('Hat', 'Pats');
        $labels = $pt->getArguments()['labels'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($labels['name'] === 'Pats');
        $this->assertTrue($labels['view_items'] === 'View Pats');

    }

    public function testPostTypeRegPluralAsSettings()
    {
        $pt = new PostType('Hat', ['description' => 'a desc']);
        $desc = $pt->getArguments()['description'];

        $this->assertTrue($pt->getId() === 'hat');
        $this->assertTrue($desc === 'a desc');
    }

    public function testPostTypeRegWithId()
    {
        $pt = new PostType('Hat', 'Hats', null, 'happy');

        $this->assertTrue($pt->getId() === 'happy');
    }

    public function testPostTypeRegExisting()
    {
        $pt = new PostType('Art', 'Arts', null, 'post');

        $this->assertTrue($pt->getId() === 'post');
        $this->assertTrue($pt->getExisting() instanceof \WP_Post_Type);
    }

    public function testPostTypeRegHandler()
    {
        $pt = new PostType('Car', 'Cars', null, 'car');
        $pt->addToRegistry();
        $pt->setHandler(CarPostTypeController::class);

        $pt->register();


        $reg = Registry::getPostTypeResource('car');
        $this->assertTrue($pt->getId() === 'car');
        $this->assertTrue($reg['controller'] === CarPostTypeController::class);
    }
}