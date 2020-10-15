<?php
declare(strict_types=1);

namespace Http;


use PHPUnit\Framework\TestCase;
use TypeRocket\Core\Container;
use TypeRocket\Elements\BaseForm;
use TypeRocket\Http\ApplicationRoutes;
use TypeRocket\Http\CustomRequest;
use TypeRocket\Http\Redirect;
use TypeRocket\Http\Route;
use TypeRocket\Http\RouteCollection;
use TypeRocket\Http\Router;
use TypeRocket\Models\WPPost;

class RouteTest extends TestCase
{

    public function testMakeGetRoute()
    {
        $route = \TypeRocket\Http\Route::new()->get()->match('/app-test')->do(function() {
            return 'Hi';
        });

        $this->assertTrue($route instanceof Route);
    }

    public function testMakeNewStaticGetRoute()
    {
        $route = Route::new()->get()->match('/app-test')->do(function() {
            return 'Hi';
        });

        $this->assertTrue($route instanceof Route);
    }

    public function testRouteNewCollection()
    {
        \TypeRocket\Http\Route::new()->get()->match('/app-test')->do(function() {
            return 'Hi';
        });

        $count = Container::resolve(RouteCollection::class)->count();

        $route = new Route;

        $route->register(new class extends RouteCollection {});

        $count_new = Container::resolve(RouteCollection::class)->count();

        $this->assertTrue($count_new == $count);
    }

    public function testRouteDoWithArg()
    {
        \TypeRocket\Http\Route::new()->get()->match('about/([^\/]+)', ['id'])->do(function($id, RouteCollection $routes) {
            return [$id, $routes->count()];
        });

        $request = new CustomRequest([
            'path' => 'wordpress/about/1/',
            'method' => 'GET'
        ]);

        // basic
        $route = (new Router($request, [
            'root' => 'https://example.com/wordpress/'
        ], Container::resolve(RouteCollection::class) ))->detectRoute();


        $this->assertTrue($route->args['id'] === '1');

        // no slash
        $route = (new Router($request, [
            'root' => 'https://example.com/wordpress'
        ], Container::resolve(RouteCollection::class) ))->detectRoute();

        $this->assertTrue($route->args['id'] === '1');

        // http
        $route = (new Router($request, [
            'root' => 'http://example.com/wordpress'
        ], Container::resolve(RouteCollection::class) ))->detectRoute();


        $this->assertTrue($route->args['id'] === '1');
    }

    public function testRoutesCount()
    {
        $count = Container::resolve(RouteCollection::class)->count();
        $this->assertTrue($count >= '2');
    }

    public function testRoutesMatch()
    {
        $request = new CustomRequest([
            'path' => 'wordpress/app-test',
            'method' => 'GET'
        ]);

        $route = (new Router($request, [
            'root' => 'https://example.com/wordpress/'
        ], Container::resolve(RouteCollection::class)))->detectRoute();

        $matched_route = $route->path;

        $this->assertTrue($matched_route == 'app-test');
    }

    public function testRoutesMatchRoot()
    {
        $request = new CustomRequest([
            'path' => '/app-test/',
            'method' => 'GET'
        ]);

        $route = (new Router($request, [
            'root' => 'https://example.com/wordpress/'
        ], Container::resolve(RouteCollection::class) ))->detectRoute();

        $matched_route = $route->path;

        $this->assertTrue($matched_route == 'app-test/');
    }

    public function testRouteNamedWithHelpers()
    {
        /** @var RouteCollection $routes */
        \TypeRocket\Http\Route::new()
            ->get()
            ->match('/about/me/(.+)', ['id'])
            ->name('about.me', '/about/me/:id/:given-name');

        $located = \TypeRocket\Http\RouteCollection::getFromContainer()->getNamedRoute('about.me');

        $built = $located->buildUrlFromPattern([
            ':id' => 123,
            'given-name' => 'kevin'
        ], false);

        $built_helper = \TypeRocket\Http\Route::buildUrl('about.me', [
            ':id' => 987,
            'given-name' => 'ben'
        ], false);

        $this->assertTrue($built == '/about/me/123/kevin/');
        $this->assertTrue($built_helper == '/about/me/987/ben/');

        $form = new BaseForm('post', 'update', 1, WPPost::class);

        $formUrl = $form->toRoute('about.me')->getFormUrl();
        $this->assertContains('/about/me/1/:given-name', $formUrl);
    }

    public function testRouteNamedNoPatternAutoDetect()
    {
        $routes = new RouteCollection();
        $route = new Route();

        $route
            ->get()
            ->post()
            ->match('/user/(.+)/job/([^\/]+)/', ['id', 'job'])
            ->name('testing.no.pattern')
            ->register($routes);

        $located = $routes->getNamedRoute('testing.no.pattern');

        $built = $located->buildUrlFromPattern([
            ':id' => 123,
            ':job' => 987,
        ]);

        $this->assertContains('/user/123/job/987', $built);
    }

    public function testRedirectToRoute()
    {
        $redirect = new Redirect();
        $redirect->toRoute('about.me', [
            'id' => 345,
            'given-name' => 'kevin'
        ]);

        $this->assertContains('/about/me/345/kevin', $redirect->url);
    }

}