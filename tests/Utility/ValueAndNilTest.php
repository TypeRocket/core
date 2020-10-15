<?php
namespace TypeRocket\tests\Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Nil;
use TypeRocket\Utility\Value;

class ValueAndNilTest extends TestCase
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
}
