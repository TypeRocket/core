<?php
declare(strict_types=1);

namespace Http;


use PHPUnit\Framework\TestCase;
use TypeRocket\Http\CustomRequest;
use TypeRocket\Http\Routes;

class RouteTest extends TestCase
{

    public function testRoutesMatch()
    {
        tr_route()->get()->match('/app-test')->do(function() {
            return 'Hi';
        });

        $request = new CustomRequest([
            'path' => 'wordpress/app-test',
            'method' => 'GET'
        ]);

        $route = (new Routes())->detectRoute([
            'match' => 'site_url'
        ], $request, 'https://example.com/wordpress/');

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'wordpress/app-test');
    }

    public function testRoutesMatchRoot()
    {
        $request = new CustomRequest([
            'path' => '/app-test/',
            'method' => 'GET'
        ]);

        $route = (new Routes())->detectRoute(null, $request, 'https://example.com/wordpress/');

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'app-test/');
    }

}