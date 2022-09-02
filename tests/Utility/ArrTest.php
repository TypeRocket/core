<?php
namespace Utility;

use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use JetBrains\PhpStorm\Internal\TentativeType;
use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Arr;
use TypeRocket\Utility\Traits\ArrayAccessible;
use TypeRocket\Utility\Traits\ArrayIterable;
use YoastSEO_Vendor\League\OAuth2\Client\Tool\ArrayAccessorTrait;

class ArrTest extends TestCase
{
    public function testIndexBy()
    {
        $data = [
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'kat'],
        ];

        $v = Arr::indexBy('name', $data);

        $this->assertTrue(array_key_exists('kim', $v));
    }

    public function testArrayReplaceAdvanced()
    {
        $current = [
            'one' => ['two'  => 10],
            'index' => ['a' => 1, 'b' => 2, 'c' => 3],
            'remove' => ['two'  => 20],
            'remove_to_str' => ['two'  => 20],
            'kept' => ['two'  => 20],
            'deep' => ['remove'  => [1,2]],
        ];

        $new = [
            'index' => ['b' => 2, 'c' => 3, 'a' => 1],
            'one' => ['two'  => 20],
            'remove' => [],
            'remove_to_str' => 'string',
            'deep' => ['remove' => [1]],
        ];

        $expected = [
            'index' => ['b' => 2, 'c' => 3, 'a' => 1],
            'one' => ['two'  => 20],
            'remove' => [],
            'remove_to_str' => 'string',
            'deep' => ['remove' => [1]],
            'kept' => ['two'  => 20],
        ];

        $returned = Arr::replaceRecursivePreferNew($current, $new, [
            'remove',
            'kept.two',
            'deep.remove',
        ]);

        $this->assertTrue($expected === $returned);
    }

    public function testArrayReplaceSimple()
    {
        $current = [
            'one' => ['two'  => 10],
        ];

        $new = [
            'one' => ['two'  => 20],
        ];

        $expected = [
            'one' => ['two'  => 20]
        ];

        $returned = Arr::replaceRecursivePreferNew($current, $new);

        $this->assertTrue($expected === $returned);
    }

    public function testArrayReplaceSimpleList()
    {
        $current = [
            'one' => ['two'  => [1,2,3]],
        ];

        $new = [
            'one' => ['two' => [3,2]],
        ];

        $expected = [
            'one' => ['two' => [3,2,3]]
        ];

        $returned = Arr::replaceRecursivePreferNew($current, $new);

        $this->assertTrue($expected === $returned);
    }

    public function testPluckSimple()
    {
        $data = [
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'kat'],
        ];

        $v = Arr::pluck($data, 'name', 'name');

        $this->assertTrue(array_key_exists('kim', $v) && $v['kim'] === 'kim');
    }

    public function testPluckComplex()
    {
        $data = [
            ['name' => 'jim', 'age' => 2, 'meta' => ['job' => 'dev']],
            ['name' => 'kim', 'age' => 3, 'meta' => ['job' => 'pm']],
            ['name' => 'kat', 'age' => 4, 'meta' => ['job' => 'ceo']],
        ];

        $v = Arr::pluck($data, ['age', 'meta'], 'age');

        $this->assertTrue(array_key_exists('2', $v) && $v[2]['meta']['job'] === 'dev');
        $this->assertTrue(($v[2]['name'] ?? null) === null);
    }

    public function testIndexByFailSameKey()
    {
        $data = [
            ['name' => 'jim'],
            ['name' => 'kim'],
            ['name' => 'jim'],
        ];

        try {
            $v = Arr::indexBy('name', $data);
        } catch (\Exception $e) {
            $this->assertTrue($e->getMessage() === 'Array list required and array key must be unique for Arr::indexBy.');
        }
    }

    public function testIndexByFailNotArray()
    {
        $data = [
            'kevin',
            ['name' => 'kim'],
            ['name' => 'jim'],
        ];

        try {
            $v = Arr::indexBy('name', $data);
        } catch (\Exception $e) {
            $this->assertTrue($e->getMessage() === 'Array list required and array key must be unique for Arr::indexBy.');
        }
    }

    public function testArraySequential()
    {
        $this->assertTrue(Arr::isSequential(['0' => '', '1' => null]));
        $this->assertTrue(Arr::isSequential([1,3,4,10,'k']));
        $this->assertTrue(!Arr::isSequential([1 => 1,3,4,10,'k']));
        $this->assertTrue(!Arr::isSequential([]));
    }

    public function testArrayAccessible()
    {
        $this->assertTrue(Arr::isAccessible(['0' => '', '1' => null]));
        $this->assertTrue(Arr::isAccessible(new \ArrayObject));
    }

    public function testArrayAssociative()
    {
        $this->assertTrue(Arr::isAssociative(['0' => '', '' => null]));
        $this->assertTrue(Arr::isAssociative(['one' => '', 'two' => null]));
        $this->assertTrue(!Arr::isAssociative(['0' => '', '1' => null]));
        $this->assertTrue(!Arr::isAssociative([1,2,3]));
    }

    public function testArraySuperEmpty()
    {
        $this->assertTrue(Arr::isEmptyArray([]));
        $this->assertTrue(!Arr::isEmptyArray(['']));
        $this->assertTrue(!Arr::isEmptyArray([[]]));
        $this->assertTrue(!Arr::isEmptyArray('[[]]'));
        $this->assertTrue(!Arr::isEmptyArray(0));
    }

    public function testArrayGet()
    {
        $data = ['one' => ['two' => null]];
        $this->assertTrue(Arr::get($data, 'one.two') === null);
        $this->assertTrue(Arr::get($data, 'one.two', true) === null);
        $this->assertTrue(Arr::get($data, 'one.two.three', true));
        $this->assertTrue(Arr::get($data, 'one') === ['two' => null]);
    }

    public function testArrayHasMany()
    {
        $data = ['one' => ['two' => null]];
        $this->assertTrue(Arr::has($data, ['one.two', 'one']));
        $this->assertTrue(!Arr::has($data, ['one.two', 'one', 'three']));
        $this->assertTrue(!Arr::has($data, ['one.two', 'one', '']));
    }

    public function testArrayHasManyArrayObject()
    {
        $class = new \ArrayObject();
        $class['one'] = ['two' => null];

        $this->assertTrue(Arr::has($class, ['one.two', 'one']));
        $this->assertTrue(!Arr::has($class, ['one.two', 'one', 'three']));
        $this->assertTrue(!Arr::has($class, ['one.two', 'one', '']));
    }

    public function testArrayHasManyArrayAccess()
    {
        $class = new class implements \ArrayAccess, \Iterator {

            use ArrayAccessible, ArrayIterable;

            protected array $storage = [];
            protected string $_location = 'storage';

            public function __construct() {
                $this->storage = [
                    "one"   => ['two' => null],
                    "99"   => 99,
                ];
            }
        };

        $this->assertTrue(Arr::has($class, ['one.two', 'one']));
        $this->assertTrue(!Arr::has($class, ['one.two', 'one', 'three']));
        $this->assertTrue(!Arr::has($class, ['one.two', 'one', '']));
    }

    public function testArrayHasManyArrayAccessIterator()
    {
        $class = new class implements \Iterator, \ArrayAccess {

            use ArrayAccessible, ArrayIterable;

            protected array $storage = [];
            protected string $_location = 'storage';

            public function __construct() {
                $this->storage = [
                    "one"   => ['two' => null],
                    "two"   => ['two' => 0],
                    "three"   => 0,
                ];
            }
        };

        $class['four'] = null;

        $this->assertTrue($class['three'] === 0);
        $this->assertTrue($class['four'] === null);

        // run once
        foreach ($class as $key => $value) {
            if(in_array($key, ['one', 'two'])) {
                $this->assertTrue(Arr::has($value, 'two'));
                $this->assertTrue(Arr::has($value, ['two']));
                $this->assertTrue( ! Arr::has($value, ['ten']));
                $this->assertTrue( ! Arr::has($value, ['ten']));
            } elseif( $key === 'three' ) {
                $this->assertTrue($value === 0);
            } else {
                $this->assertTrue($value === null);
            }
        }

        // run twice
        foreach ($class as $key => $value) {
            if(in_array($key, ['one', 'two'])) {
                $this->assertTrue(Arr::has($value, 'two'));
                $this->assertTrue(Arr::has($value, ['two']));
                $this->assertTrue( ! Arr::has($value, ['ten']));
                $this->assertTrue( ! Arr::has($value, ['ten']));
            } elseif( $key === 'three' ) {
                $this->assertTrue($value === 0);
            } else {
                $this->assertTrue($value === null);
            }
        }

        $this->assertTrue(Arr::has($class, ['one', 'two']));
    }

    public function testArrayHasManyArrayIteratorAssoc()
    {
        $class = new class implements \Iterator {

            use ArrayIterable;

            protected array $storage = [];
            protected string $_location = 'storage';

            public function __construct() {
                $this->storage = [
                    "one"   => ['two' => null],
                    "two"   => ['two' => 0],
                    "three"   => 0,
                ];
            }
        };

        // run once
        foreach ($class as $key => $value) {
            if(in_array($key, ['one', 'two'])) {
                $this->assertTrue(Arr::has($value, 'two'));
                $this->assertTrue(Arr::has($value, ['two']));
                $this->assertTrue( ! Arr::has($value, ['ten']));
                $this->assertTrue( ! Arr::has($value, ['ten']));
            } elseif( $key === 'three' ) {
                $this->assertTrue($value === 0);
            } else {
                $this->assertTrue($value === null);
            }
        }
    }

    public function testArrayHasManyArrayIteratorSequential()
    {
        $class = new class implements \Iterator {

            use ArrayIterable;

            protected array $storage = [];
            protected string $_location = 'storage';

            public function __construct() {
                $this->storage = [
                    ['two' => null],
                    ['two' => 0],
                    0,
                ];
            }
        };

        // run once
        foreach ($class as $key => $value) {
            if(in_array($key, [0,1])) {
                $this->assertTrue(Arr::has($value, 'two'));
                $this->assertTrue(Arr::has($value, ['two']));
                $this->assertTrue( ! Arr::has($value, ['ten']));
                $this->assertTrue( ! Arr::has($value, ['ten']));
            } elseif( $key === 2 ) {
                $this->assertTrue($value === 0);
            } else {
                $this->assertTrue($value === null);
            }
        }
    }

    public function testArrayHas()
    {
        $data = ['one' => ['two' => null]];
        $this->assertTrue(Arr::has($data, 'one.two'));
        $this->assertTrue(Arr::has($data, 'one'));
        $this->assertTrue(!Arr::has($data, 'one.two.three'));
        $this->assertTrue(!Arr::has($data, ''));
    }

    public function testArrayFirstLast()
    {
        $array = new \ArrayObject();
        $array['one'] = 1;
        $array['two'] = 2;
        $array['three'] = 3;

        $this->assertTrue(Arr::first($array) === 1);
        $this->assertTrue(Arr::last($array) === 3);
        $this->assertTrue(Arr::last([]) === null);
        $this->assertTrue(Arr::first([]) === null);

        $this->assertTrue(Arr::first($array, function($v, $k) {
            return $k === 'two';
        }) === 2);

        $this->assertTrue(Arr::first($array, function($v, $k) {
            return $v === 3;
        }) === 3);

        $this->assertTrue(Arr::last($array, function($v, $k) {
                return $v === 10 || $k === 'ten';
        }, 4) === 4);
    }

    public function testArrayExists()
    {
        $array = [
            'one' => 3,
            1.4 => 3,
            '1.1' => 3,
            1 => 3,
        ];

        $this->assertTrue(Arr::exists($array, 'one'));
        $this->assertTrue(Arr::exists($array, 1));
        $this->assertTrue(Arr::exists($array, 1.1));
        $this->assertTrue( ! Arr::exists($array, 1.4));
        $this->assertTrue( ! Arr::exists($array, 0));
    }

    public function testArrayExistsObject()
    {
        $array = new \ArrayObject();
        $array['one'] = 3;
        $array[1.4] = 3;
        $array['1.1'] = 3;
        $array[1] = 3;

        $this->assertTrue(Arr::exists($array, 'one'));
        $this->assertTrue(Arr::exists($array, 1));
        $this->assertTrue(Arr::exists($array, 1.1));
        $this->assertTrue( ! Arr::exists($array, 1.4));
        $this->assertTrue( ! Arr::exists($array, 0));
    }

    public function testArrayFirstLastObject()
    {
        $array = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
        ];

        $this->assertTrue(Arr::first($array) === 1);
        $this->assertTrue(Arr::last($array) === 3);
        $this->assertTrue(Arr::last([]) === null);
        $this->assertTrue(Arr::first([]) === null);
    }

    public function testArraySet()
    {
        $array = [
            'one' => [
                'two' => [1,2,3]
            ]
        ];

        $new = Arr::set('one.two', $array, null);

        $this->assertTrue($new['one']['two'] === null);
    }

    public function testArraySetMissingIndex()
    {
        $array = [
            'one' => [
                'two' => [1,2,3]
            ]
        ];

        $new = Arr::set('one.three', $array, null);

        $this->assertTrue($new['one']['three'] === null);
    }
}