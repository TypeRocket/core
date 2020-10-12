<?php
declare(strict_types=1);


namespace Controllers;


use PHPUnit\Framework\TestCase;
use TypeRocket\Core\Resolver;
use TypeRocket\Http\ControllerContainer;
use TypeRocket\Http\Handler;
use TypeRocket\Http\Request;
use TypeRocket\Models\WPUser;

class ControllerContainerTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testMain()
    {
        $handler = (new Handler)->setArgs(['@first' => 1])->setController(function(WPUser $user, $id = null) {
            $this->assertTrue($user->getID() == 1);
            $this->assertTrue($id === null);

            return $user;
        });

        /** @var ControllerContainer $cc */
        $cc = (new Resolver)->resolve(ControllerContainer::class, ['handler' => $handler]);

        $cc->handle();

        $this->assertTrue($cc->getResponse()->getReturn() instanceof WPUser);
    }

    /**
     * @throws \Exception
     */
    public function testMainRestAtFirst()
    {
        $handler = (new Handler)->setArgs(['@first' => 1])->setController(function($id, WPUser $user) {
            $user->findById($id);
            $this->assertTrue($user->getID() == 1);

            return $user;
        });

        /** @var ControllerContainer $cc */
        $cc = (new Resolver)->resolve(ControllerContainer::class, ['handler' => $handler]);

        $cc->handle();

        $this->assertTrue($cc->getResponse()->getReturn() instanceof WPUser);
    }

    /**
     * @throws \Exception
     */
    public function testMainId()
    {
        $handler = (new Handler)->setArgs(['id' => 1])->setController(function($id, WPUser $user) {
            $user->findById($id);
            $this->assertTrue($user->getID() == 1);

            return $user;
        });

        /** @var ControllerContainer $cc */
        $cc = (new Resolver)->resolve(ControllerContainer::class, ['handler' => $handler]);

        $cc->handle();

        $this->assertTrue($cc->getResponse()->getReturn() instanceof WPUser);
    }

    public function testResolveByIndexMany()
    {
        $handler = (new Handler)->setArgs([2, 1, 3, 'request' => null])->setController(function($two, WPUser $user, $three, Request $request, $four = 4) {
            $this->assertTrue($two == 2);
            $this->assertTrue($user->getID() == 1);
            $this->assertTrue($three == 3);
            $this->assertTrue($four == 4);
            $this->assertTrue($request instanceof Request);

            return $user;
        });

        /** @var ControllerContainer $cc */
        $cc = (new Resolver)->resolve(ControllerContainer::class, ['handler' => $handler]);

        $cc->handle();

        $this->assertTrue($cc->getResponse()->getReturn() instanceof WPUser);
    }

    public function testResolveByNameNotFound()
    {
        $handler = (new Handler)->setArgs(['id' => 1])->setController(function(WPUser $user) {
            $this->assertTrue($user->getID() == null);

            return $user;
        });

        /** @var ControllerContainer $cc */
        $cc = (new Resolver)->resolve(ControllerContainer::class, ['handler' => $handler]);

        $cc->handle();

        $this->assertTrue($cc->getResponse()->getReturn() instanceof WPUser);
    }

    public function testResolveByIndex()
    {
        $handler = (new Handler)->setArgs([1])->setController(function(WPUser $user) {
            $this->assertTrue($user->getID() == 1);

            return $user;
        });

        /** @var ControllerContainer $cc */
        $cc = (new Resolver)->resolve(ControllerContainer::class, ['handler' => $handler]);

        $cc->handle();

        $this->assertTrue($cc->getResponse()->getReturn() instanceof WPUser);
    }

    public function testResolveByName()
    {
        $handler = (new Handler)->setArgs(['user' => 1])->setController(function(WPUser $user) {
            $this->assertTrue($user->getID() == 1);

            return $user;
        });

        /** @var ControllerContainer $cc */
        $cc = (new Resolver)->resolve(ControllerContainer::class, ['handler' => $handler]);

        $cc->handle();

        $this->assertTrue($cc->getResponse()->getReturn() instanceof WPUser);
    }

}