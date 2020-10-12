<?php
declare(strict_types=1);

namespace Http;

use PHPUnit\Framework\TestCase;
use TypeRocket\Http\Handler;
use TypeRocket\Http\Responders\HttpResponder;

class ResponderTest extends TestCase
{
    public function testHandlerArraySetMiddlewareGroups()
    {
        $handler = new Handler();

        $response = $handler
            ->setMiddlewareGroups(['post', null, 'restApi'])
            ->getMiddlewareGroups();

        $this->assertTrue($response == ['post', null, 'restApi']);
    }

    public function testHandlerStringSetMiddlewareGroups()
    {
        $handler = new Handler;

        $response = $handler
            ->setMiddlewareGroups(['TERM'])
            ->getMiddlewareGroups();

        $this->assertTrue($response == ['TERM']);
    }
}