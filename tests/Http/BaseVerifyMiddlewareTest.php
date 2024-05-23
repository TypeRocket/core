<?php
declare(strict_types=1);

namespace Http;


use PHPUnit\Framework\TestCase;
use TypeRocket\Http\Handler;
use TypeRocket\Http\Middleware\BaseVerify;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;

class BaseVerifyMiddlewareTest extends TestCase {

    public function testShorthand()
    {
        $verify = new BaseVerify(new Request(), new Response(), null, new Handler());

        $verify->except = [
            'my/path/now',
            'one/path/now/more',
            'my/*',
        ];

        $this->assertTrue($verify->excludePath('my/path/now'));
        $this->assertTrue(! $verify->excludePath('one/path/now'));
        $this->assertTrue(! $verify->excludePath('one/path/now'));
        $this->assertTrue($verify->excludePath('my/path'));
        $this->assertTrue(! $verify->excludePath('my/path/with'));
        $this->assertTrue(! $verify->excludePath('my/path/now/append'));
        $this->assertTrue(! $verify->excludePath('pre/my/path/now'));
    }

}
