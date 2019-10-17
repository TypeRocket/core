<?php
declare(strict_types=1);

namespace Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Core\Injector;
use TypeRocket\Http\ApplicationRoutes;
use TypeRocket\Http\Response;

class InjectorTest extends TestCase
{

    public function testRegisterAndDestroy()
    {

        Injector::register('test.response.404', function () {
            $response = new Response();
            $response->setStatus(404);

            return $response;
        }, true);

        $response = Injector::resolve('test.response.404');

        $this->assertTrue( $response->getStatus() == 404 );

        Injector::destroy('test.response.404');

        $response = Injector::resolve('test.response.404');

        $this->assertTrue( !$response );
    }

    public function testInjectorHelper()
    {
        $routes = tr_container('routes');
        $db = tr_container('database');

        $this->assertTrue($routes instanceof ApplicationRoutes);
        $this->assertTrue($db instanceof \wpdb);
    }

}