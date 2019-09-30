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

    public function testRoutesMatch()
    {
        $request = new CustomRequest([
            'path' => 'wordpress/app-test',
            'method' => 'GET'
        ]);

        $route = (new Routes($request))->detectRoute([
            'match' => 'site_url'
        ], 'https://example.com/wordpress/');

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'wordpress/app-test');
    }

    public function testRoutesMatchRoot()
    {
        $request = new CustomRequest([
            'path' => '/app-test/',
            'method' => 'GET'
        ]);

        $route = (new Routes($request))->detectRoute(null, 'https://example.com/wordpress/');

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'app-test/');
    }

}