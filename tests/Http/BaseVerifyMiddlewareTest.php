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
            'my/*',
        ];

        $e = $verify->excludePath('my/path/now');
        $e1 = $verify->excludePath('my/path');
        $e2 = $verify->excludePath('my/path/with');

        $this->assertTrue($e);
        $this->assertTrue($e1);
        $this->assertTrue(!$e2);
    }

}
