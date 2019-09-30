<?php
declare(strict_types=1);

namespace Http;


use PHPUnit\Framework\TestCase;
use TypeRocket\Http\CustomRequest;
use TypeRocket\Http\Route;
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

    public function testRouteDoWithArg()
    {
        tr_route()->get()->match('about/(.*)', ['id'])->do(function($id) {
            return $id;
        });

        $request = new CustomRequest([
            'path' => 'wordpress/about/1',
            'method' => 'GET'
        ]);

        $route = (new Routes($request, [
            'root' => 'https://example.com/wordpress/'
        ]))->detectRoute();

        $id = call_user_func_array($route->match[1]->do, $route->match[2]);

        $this->assertTrue($id == '1');
    }

    public function testRoutesMatch()
    {
        $request = new CustomRequest([
            'path' => 'wordpress/app-test',
            'method' => 'GET'
        ]);

        $route = (new Routes($request, [
            'root' => 'https://example.com/wordpress/'
        ]))->detectRoute();

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
        ]))->detectRoute();

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'app-test/');
    }

}