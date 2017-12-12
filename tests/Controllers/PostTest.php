<?php
declare(strict_types=1);

namespace Controllers;

use PHPUnit\Framework\TestCase;
use TypeRocket\Controllers\WPPostController;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Models\WPPost;

class PostTest extends TestCase
{
    public function testUpdateWithMetaMethod()
    {
        $_POST['tr']['post_title'] = 'Hello World! Updated by controller!';
        $_POST['tr']['meta_key'] = 'Hello World! Updated by controller!';
        $_POST = wp_slash($_POST);

        $request = new Request();
        $response = new Response();
        $controller = new WPPostController( $request, $response );
        $controller->update( 1 );

        $model = new WPPost();
        $meta = $model->findById( $response->getData('resourceId') )->getFieldValue('meta_key');

        $this->assertTrue( $response->getData('resourceId') == 1 );
        $this->assertTrue( $meta == $request->getFields('meta_key') );
    }

    public function testCreateWithMetaMethod()
    {
        $_POST['tr']['post_title'] = 'Hello World! Created by controller!';
        $_POST['tr']['post_content'] = 'Content created by controller!';
        $_POST['tr']['meta_key'] = 'Meta created by controller!';
        $_POST = wp_slash($_POST);

        $request = new Request();
        $response = new Response();
        $controller = new WPPostController( $request, $response );
        $controller->create();
        $id = $response->getData('resourceId');
        $model = new WPPost();
        $meta = $model->findById( $id )->getFieldValue('meta_key');

        wp_delete_post( $id, true);

        $this->assertTrue( !empty($id) );
        $this->assertTrue( $meta == $request->getFields('meta_key') );
    }

}