<?php
declare(strict_types=1);

namespace Http;

use PHPUnit\Framework\TestCase;
use TypeRocket\Http\Handler;
use TypeRocket\Http\Responders\ResourceResponder;

class ResponderTest extends TestCase
{
    public function testHandlerArraySetMiddlewareGroups()
    {
        $handler = new Handler();

        $response = $handler
            ->setMiddlewareGroups(['post', null, 'restApi'])
            ->getMiddlewareGroups();

        $this->assertTrue($response == ['post', 'restapi']);
    }

    public function testHandlerStringSetMiddlewareGroups()
    {
        $handler = new Handler();

        $response = $handler
            ->setMiddlewareGroups('TERM')
            ->getMiddlewareGroups();

        $this->assertTrue($response == ['term']);
    }

    public function testResourceResponderStringSetMiddlewareGroups()
    {
        $responder = new ResourceResponder();

        $response = $responder
            ->setMiddlewareGroups('TERM')
            ->getMiddlewareGroups();

        $this->assertTrue($response == ['term']);
    }

    public function testResourceResponderArraySetMiddlewareGroups()
    {
        $handler = new ResourceResponder();

        $response = $handler
            ->setMiddlewareGroups(['post', null, 'restApi'])
            ->getMiddlewareGroups();

        $this->assertTrue($response == ['post', 'restapi']);
    }
}