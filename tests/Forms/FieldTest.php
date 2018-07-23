<?php
declare(strict_types=1);

namespace Forms;

use PHPUnit\Framework\TestCase;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Fields\Text;
use TypeRocket\Elements\Form;

class FieldTest extends TestCase
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