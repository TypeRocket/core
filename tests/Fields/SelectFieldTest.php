<?php
declare(strict_types=1);

namespace TypeRocket\tests\Fields;


use PHPUnit\Framework\TestCase;
use TypeRocket\Elements\Fields\Select;
use TypeRocket\Models\WPPost;

class SelectFieldTest extends TestCase
{
    public function testSelectFieldNoOptions()
    {
        $text = (new Select('post_title', ['data-test' => 'X']))->setModel((new WPPost)->find(1))->getString();

        $id = (int) wp_unique_id();
        $id--;

        $this->assertStringContainsString("id=\"tr_field_${id}_tr_post_title\"", $text);
        $this->assertStringContainsString('data-test="X"', $text);
    }

    public function testSelectFieldOptions()
    {
        $text = (new Select('post_title'))->removeAttribute('id')->setOptions(['t' => '', 'c' => 'c'])->setModel((new WPPost)->find(1))->getString();

        $this->assertStringContainsString('name="tr[post_title]', $text);
        $this->assertStringContainsString('<option value>t</option>', $text);
        $this->assertStringContainsString('<option value="c">c</option>', $text);
    }
}