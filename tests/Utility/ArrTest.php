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