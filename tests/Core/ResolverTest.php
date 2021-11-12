<?php
declare(strict_types=1);

namespace Core;

use PHPUnit\Framework\TestCase;
use TypeRocket\Core\Resolver;
use TypeRocket\Models\Model;

class ForResolverTestClass {

    public function __construct(
        ?TestCase $test,
        ?Model $model = null,
        array $array = [],
        string $str = 'text'
    )
    {
        $test->assertInstanceOf(Model::class, $model);
        $test->assertIsArray($array);
        $test->assertIsString($str);
    }

}

class ResolverTest extends TestCase
{
    public function test()
    {
        Resolver::new()->resolve(ForResolverTestClass::class, ['test' => $this]);
    }

    public function testResolveCallable()
    {
        Resolver::new()->resolveCallable(function($array, $int, $string, $bool, $null = null) {
            $this->assertIsArray($array);
            $this->assertIsInt($int);
            $this->assertIsString($string);
            $this->assertIsBool($bool);
            $this->assertTrue(is_null($null));
        }, [
           'array' => [1,2,3],
           'int' => 1,
           'string' => 'text',
           'bool' => false,
        ]);

        Resolver::new()->resolveCallable(function($array, $int, $string, $bool) {
            $this->assertIsArray($array);
            $this->assertIsInt($int);
            $this->assertIsString($string);
            $this->assertIsBool($bool);
        }, [
            [1,2,3],
            1,
            'string',
            false
        ]);

        Resolver::new()->resolveCallable(function($int, $array, $string, $bool) {
            $this->assertIsArray($array);
            $this->assertIsInt($int);
            $this->assertIsString($string);
            $this->assertIsBool($bool);
        }, [
            'array' => [1,2,3],
            1,
            'string',
            false
        ]);
    }
}