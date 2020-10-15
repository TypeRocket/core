<?php
declare(strict_types=1);

namespace Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Core\Container;
use TypeRocket\Http\ApplicationRoutes;
use TypeRocket\Http\Response;
use TypeRocket\Http\RouteCollection;

class InjectorTest extends TestCase
{

    public function testRegisterAndDestroy()
    {

        Container::register('test.response.404', function () {
            $response = new Response();
            $response->setStatus(404);

            return $response;
        }, true);

        $response = Container::resolve('test.response.404');

        $this->assertTrue( $response->getStatus() == 404 );
    }

    public function testInjectorHelper()
    {
        $routes = \TypeRocket\Core\Container::resolveAlias('routes');

        $this->assertTrue($routes instanceof RouteCollection);
    }

}