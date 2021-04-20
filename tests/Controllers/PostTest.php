<?php
declare(strict_types=1);

namespace Controllers;

use PHPUnit\Framework\TestCase;
use TypeRocket\Controllers\WPPostController;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPUser;

class PostTest extends TestCase
{
    public function testUpdateWithMetaMethod()
    {
        $_POST['tr']['post_title'] = 'Hello World! Updated by controller!';
        $_POST['tr']['meta_key'] = 'Hello World! Updated by controller!';
        $_POST = wp_slash($_POST);

        $request = new Request();
        $response = new Response();
        $controller = new WPPostController();
        $user = (new WPUser)->find(1);
        $controller->update(1, $request, $response, $user);

        $model = new WPPost;
        $meta = $model->findById( $response->getData('resourceId') )->getFieldValue('meta_key');

        $this->assertTrue( $response->getData('resourceId') == 1 );
        $this->assertTrue( $meta == $request->getFields('meta_key') );
        unset($_POST['tr']);
    }

    public function testCreateWithMetaMethod()
    {
        $_POST['tr']['post_title'] = 'Hello World! Created by controller!';
        $_POST['tr']['post_content'] = 'Content created by controller!';
        $_POST['tr']['meta_key'] = 'Meta created by controller!';
        $_POST = wp_slash($_POST);

        $request = new Request();
        $response = new Response();
        $controller = new WPPostController;
        $user = (new WPUser)->find(1);
        $controller->create($request, $response, $user);
        $id = $response->getData('resourceId');
        $model = new WPPost();
        $meta = $model->findById( $id )->getFieldValue('meta_key');

        wp_delete_post( $id, true);

        $this->assertTrue( !empty($id) );
        $this->assertTrue( $meta == $request->getFields('meta_key') );
        unset($_POST['tr']);
    }

    public function testCreateWithMetaMethodAndOnAction()
    {
        $_POST['tr']['post_content'] = 'needed to enter builtin requirment';
        $_POST['tr']['model'] = null;
        $_POST['tr']['type'] = null;

        $controller = new class extends WPPostController {
            public function onActionSave($type, $model)
            {
                global $myTestOnActionModel;

                $_POST['tr']['type'] = $type;
                $_POST['tr']['model'] = $model;
            }
        };

        $request = new Request();
        $response = new Response();
        $user = (new WPUser)->find(1);
        $message = null;

        $controller->create($request, $response, $user);
        $id = $response->getData('resourceId');
        $hasErrors = $response->hasErrors();

        if($id) {
            // cleanup just in case but this should not run
            wp_delete_post( $response->getData('resourceId'), true);
        }

        $this->assertTrue( $hasErrors === true );
        $this->assertTrue( $_POST['tr']['model'] instanceof WPPost);
        $this->assertTrue( $_POST['tr']['type'] === 'create');
        unset($_POST['tr']);
    }

}