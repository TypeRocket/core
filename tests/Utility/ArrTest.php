<?php
namespace Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Arr;

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
}