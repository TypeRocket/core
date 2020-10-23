<?php
namespace TypeRocket\tests\Utility;


use PHPUnit\Framework\TestCase;
use TypeRocket\Elements\Fields\Text;
use TypeRocket\Html\Element;
use TypeRocket\Html\Html;
use TypeRocket\Models\WPPost;

class HtmlTest extends TestCase
{

    public function testHtml()
    {
        $string1 = Html::el('k', 'about', 'b')->getString();
        $string = Html::el('k', 'b')->getString();
        $string2 = Html::el('k', ['c' => 'x'], 'b')->getString();
        $a = Html::a('link', '#example')->getString();
        $img = Html::img('#example')->getString();
        $input = Html::input('text', 'ex', '"a!b!c"')->getString();
        $div = Html::div(['class' => '"a!b!c"'], '"a!b!c"')->getString();

        $this->assertTrue($string1 == '<k>b</k>');
        $this->assertTrue($string == '<k>b</k>');
        $this->assertTrue($string2 == '<k c="x">b</k>');
        $this->assertTrue($a == '<a href="#example">link</a>');
        $this->assertTrue($img == '<img src="#example" />');
        $this->assertTrue($input == '<input type="text" name="ex" value="&quot;a!b!c&quot;" />');
        $this->assertTrue($div == '<div class="&quot;a!b!c&quot;">"a!b!c"</div>');
    }

    public function testTestField()
    {
        $v = (new WPPost)->find(1);
        $text = (new Text('post_title', ['data-test' => 'X']))->setModel($v)->getString();

        $this->assertStringContainsString('data-test="X"', $text);
        $this->assertStringContainsString('value="Hello', $text);
    }

    public function testCloseElement()
    {
        $html = Element::controlButton(['class' => 'testing'])->getString();

        $this->assertTrue('<button aria-label="Close" class="testing" type="button"><span aria-hidden="true">×</span></button>' == $html);
    }

}