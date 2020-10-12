<?php
declare(strict_types=1);

namespace Forms;

use PHPUnit\Framework\TestCase;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Fields\Text;
use TypeRocket\Elements\BaseForm;
use TypeRocket\Utility\DataCollection;

class FieldTest extends TestCase
{

    public function testFieldMacro()
    {
        $field = new Text('name', new BaseForm() );
        $returned_field = $field->setMacro('testMacro', function() {
            $this->setType( 'testMacro' );
            return $this;
        })->testMacro();

        $this->assertInstanceOf(Field::class, $returned_field);
        $this->assertTrue('testMacro' == $returned_field->getType() );
    }

    public function testFieldGroupFormGroup()
    {
        $form = new BaseForm();
        $field = new Text('name', $form->group('meta') );
        $dots = $field->getDots();

        $this->assertTrue('meta.name' == $dots );

        $field->group('deeper');
        $dots = $field->getDots();
        $this->assertTrue('meta.deeper.name' == $dots );
    }

    public function testFormCustomFormFieldData()
    {
        $data = [
            'meta' => ['deep_test' => 'nested value']
        ];

        $field = new Text('meta.Deep Test');
        $dots = $field->getDots();
        $field->setModel($data);
        $value = $field->getValue();

        $this->assertTrue('meta.deep_test' == $dots );
        $this->assertTrue('nested value' == $value );
    }

}