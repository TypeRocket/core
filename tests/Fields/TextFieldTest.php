<?php


namespace TypeRocket\tests\Fields;


use PHPUnit\Framework\TestCase;
use TypeRocket\Elements\Fields\Text;
use TypeRocket\Models\WPPost;

class TextFieldTest extends TestCase
{

    public function testTestField()
    {
        $v = (new WPPost)->find(1);
        $text = (new Text('post_title', ['data-test' => 'X']))->setModel($v)->getString();

        $this->assertStringContainsString('value="'.esc_attr($v->post_title).'"', $text);
        $this->assertStringContainsString('data-test="X"', $text);
    }

}