<?php

namespace Controllers;

use TypeRocket\Controllers\WPPostController;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;

class PostTest extends \PHPUnit_Framework_TestCase
{
    public function testUpdateMethod()
    {
        $_POST['tr']['post_title'] = 'Hello World! Updated by controller!';
        $request = new Request();
        $response = new Response();
        $controller = new WPPostController( $request, $response );
        $controller->update( 1 );

        $this->assertTrue( $response->getData('resourceId') == 1 );
    }
}