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

    public function testTestFieldSetDisplayCapability()
    {
        $v = (new WPPost)->find(1);
        $text = (new Text('post_title', ['data-test' => 'X']))->setModel($v);
        $text->setDisplayCapability('nothing_named_this');
        $text_none = $text->getString();
        $text->setDisplayCapability(null);
        $text_some = $text->getString();

        $this->assertTrue($text_none === '');
        $this->assertStringContainsString('data-test="X"', $text_some);
    }

}