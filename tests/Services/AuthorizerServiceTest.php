<?php
declare(strict_types=1);

namespace Services;

use App\Models\Post;
use PHPUnit\Framework\TestCase;
use TypeRocket\Core\Container;
use TypeRocket\Models\AuthUser;
use TypeRocket\Models\WPUser;
use TypeRocket\Services\AuthorizerService;


class AuthorizerServiceTest extends TestCase
{
    public function testDeepModelInheritance()
    {
        $pt = new class extends Post {};

        $auth = new AuthorizerService([
            '\TypeRocket\Models\WPPost' => '\App\Auth\PostPolicy',
        ]);
        $user = WPUser::new()->find(1);
        $delete = $auth->authRegistered($user, $pt, 'destroy');

        $this->assertTrue( $delete );
    }
}