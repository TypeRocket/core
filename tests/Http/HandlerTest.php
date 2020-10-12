<?php

namespace TypeRocket\tests\Http;

use PHPUnit\Framework\TestCase;
use TypeRocket\Controllers\WPPostController;
use TypeRocket\Http\Handler;

class HandlerTest extends TestCase
{


    public function testShorthand()
    {
        $handler = (new Handler)->setController('kevin@Post');

        $controller = $handler->getController();

        $this->assertTrue($controller[1] == 'kevin');
        $this->assertTrue($controller[0] instanceof WPPostController);
    }

    public function testArray()
    {
        $handler = (new Handler)->setController([WPPostController::class, 'kevin']);

        $controller = $handler->getController();

        $this->assertTrue($controller[1] == 'kevin');
        $this->assertTrue($controller[0] instanceof WPPostController);
    }

    public function testCallable()
    {
        $handler = (new Handler)->setController(function () {});

        $controller = $handler->getController();

        $this->assertTrue(is_callable($controller));
    }

}