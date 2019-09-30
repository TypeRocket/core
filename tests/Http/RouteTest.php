<?php
declare(strict_types=1);

namespace Http;


use PHPUnit\Framework\TestCase;
use TypeRocket\Core\Injector;
use TypeRocket\Http\CustomRequest;
use TypeRocket\Http\Route;
use TypeRocket\Http\RouteCollection;
use TypeRocket\Http\Routes;

class RouteTest extends TestCase
{

    public function testMakeGetRoute()
    {
        $route = tr_route()->get()->match('/app-test')->do(function() {
            return 'Hi';
        });

        $this->assertTrue($route instanceof Route);
    }

    public function testRouteNewCollection()
    {
        tr_route()->get()->match('/app-test')->do(function() {
            return 'Hi';
        });

        $count = Injector::resolve(RouteCollection::class)->count();

        $route = new Route;

        $route->registerRoute(new class extends RouteCollection {});

        $count_new = Injector::resolve(RouteCollection::class)->count();

        $this->assertTrue($count_new == $count);
    }

    public function testRouteDoWithArg()
    {
        tr_route()->get()->match('about/([^\/]+)', ['id'])->do(function($id, RouteCollection $routes) {
            return [$id, $routes->count()];
        });

        $request = new CustomRequest([
            'path' => 'wordpress/about/1/',
            'method' => 'GET'
        ]);

        $route = (new Routes($request, [
            'root' => 'https://example.com/wordpress/'
        ], Injector::resolve(RouteCollection::class) ))->detectRoute();

        $map = resolve_method_args($route->match[1]->do,  $route->match[2]);
        $response = resolve_method_map($map);

        $this->assertTrue($response[0] == '1' && $response[1] >= 2);
    }

    public function testRoutesCount()
    {
        $count = Injector::resolve(RouteCollection::class)->count();
        $this->assertTrue($count >= '2');
    }

    public function testRoutesMatch()
    {
        $request = new CustomRequest([
            'path' => 'wordpress/app-test',
            'method' => 'GET'
        ]);

        $route = (new Routes($request, [
            'root' => 'https://example.com/wordpress/'
        ], Injector::resolve(RouteCollection::class)))->detectRoute();

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'wordpress/app-test');
    }

    public function testRoutesMatchRoot()
    {
        $request = new CustomRequest([
            'path' => '/app-test/',
            'method' => 'GET'
        ]);

        $route = (new Routes($request, [
            'root' => 'https://example.com/wordpress/'
        ], Injector::resolve(RouteCollection::class) ))->detectRoute();

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'app-test/');
    }

}