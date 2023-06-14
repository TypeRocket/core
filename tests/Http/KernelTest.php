<?php

namespace Http;

use PHPUnit\Framework\TestCase;
use TypeRocket\Controllers\WPPostController;
use TypeRocket\Http\Handler;
use TypeRocket\Http\HttpKernel;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;

class KernelTest extends TestCase
{
    public function testKernelMiddleware()
    {
        $kernel = new class (new Request, new Response, new Handler) extends HttpKernel {
            protected $middleware = [
                'test' => [
                    1,2,3
                ]
            ];
        };

        $kernel->middleware('test', function($middleware) {

            $middleware[] = 4;
            $middleware[0] = 0;

            return $middleware;
        });


        $this->assertTrue($kernel->middleware('test')[0] === 0);
        $this->assertTrue($kernel->middleware('test')[3] === 4);
    }
}