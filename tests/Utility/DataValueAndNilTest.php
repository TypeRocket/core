<?php
namespace TypeRocket\tests\Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Data;
use TypeRocket\Utility\Nil;
use TypeRocket\Utility\Value;

class DataValueAndNilTest extends TestCase
{
    public function testNilObj()
    {
        $obj = new \stdClass();
        $obj->one = new \stdClass();
        $obj->one->two = new \stdClass();
        $null = Value::nils($obj)->one->two->three->get() ?? null;

        $this->assertTrue(is_null($null));
        $this->assertTrue(Value::nils($obj->one)->two->three->four instanceof Nil);
        $this->assertTrue(isset(Value::nils($obj->one)->two));
        $this->assertTrue(!isset(Value::nils($obj->one->two)->three));
        $this->assertTrue(!isset(Value::nils($obj->one->two)->three->four));
    }

    public function testNilArray()
    {
        $arr = [];
        $arr['one'] = [];
        $arr['one']['two'] = [true];
        $this->assertTrue(Value::nils(Value::nils($arr['one'])['two'])['three']['four'] instanceof Nil);
        $this->assertTrue(isset(Value::nils($arr['one'])['two']));
        $this->assertTrue(!isset(Value::nils($arr['one']['two'])['three']['four']));
    }

    public function testNilCombo()
    {
        $arr = [];
        $arr['one'] = [];
        $arr['one']['two'] = [true];

        $this->assertTrue(Value::nils(Value::nils($arr['one'])['two'])->three['four'] instanceof Nil);
        $this->assertTrue(isset(Value::nils($arr['one'])->two));
        $this->assertTrue(!isset(Value::nils($arr['one']['two'])['three']->four));
    }

    public function testNilHelper()
    {
        $arr = [];
        $arr['one'] = [];
        $arr['one']['two'] = [true];

        $this->assertTrue(Value::nils(Value::nils($arr['one'])['two'])->three['four'] instanceof Nil);
        $this->assertTrue(Value::nils(Value::nils($arr['one'])['two'])->three['four']->get() === null);
        $this->assertTrue(isset(Value::nils($arr)['one']->two));
        $this->assertTrue(!isset(Value::nils($arr['one']['two'])['three']['four']));
    }

    public function testDataWalkBasic()
    {
        $data = [
            'one' => ['two' => 'hi' ]
        ];

        $v = Data::walk('one.two', $data);

        $this->assertTrue($v === 'hi');
    }

    public function testDataWalkDeep()
    {
        $data = [
            'one' => [
                'two' => [[1],[2],[3]],
                'three' => [[4],[5],[6]],
            ]
        ];

        $v = Data::walk('one.*.0', $data);
        $v2 = Data::walk('one.*.1', $data);

        $v = array_reduce($v, function($carry, $v) {
            return $carry + $v[0];
        });

        $v2 = array_reduce($v2, function($carry, $v) {
            return $carry + $v[0];
        });

        $this->assertTrue($v === 5);
        $this->assertTrue($v2 === 7);
    }
}
