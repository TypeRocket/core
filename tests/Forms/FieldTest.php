<?php

namespace Forms;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Fields\Text;
use TypeRocket\Elements\Form;

class FieldTest extends \PHPUnit_Framework_TestCase
{

    public function testFieldMacro()
    {
        $field = new Text('name', new Form() );
        $returned_field = $field->setMacro('testMacro', function() {
            $this->setType( 'testMacro' );
            return $this;
        })->testMacro();

        $this->assertInstanceOf(Field::class, $returned_field);
        $this->assertTrue('testMacro' == $returned_field->getType() );
    }

}