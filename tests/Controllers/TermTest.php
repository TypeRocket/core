<?php
declare(strict_types=1);

namespace Controllers;

use PHPUnit\Framework\TestCase;
use TypeRocket\Controllers\WPTermController;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\WPUser;

class TermTest extends TestCase
{
    public function testUpdateWithMetaMethod()
    {
        $_POST['tr']['meta_key'] = 'Hello World! \ / \' Updated by controller!';
        $_POST['tr']['description'] = 'Update from controller \\ \TypeRocket\Name \'in quotes\'';
        $_POST = wp_slash($_POST);

        $request = new Request();
        $response = new Response();
        $controller = new WPTermController;
        $user = (new WPUser)->find(1);
        $controller->update(1, $request, $response, $user);

        $model = new WPTerm();
        $meta = $model->findById( $response->getData('resourceId') )->getFieldValue('meta_key');

        $this->assertTrue( $response->getData('resourceId') == 1 );
        $this->assertTrue( $meta == $request->getFields('meta_key') );
        unset($_POST['tr']);
    }

}