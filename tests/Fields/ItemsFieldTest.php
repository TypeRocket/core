<?php
declare(strict_types=1);

namespace TypeRocket\tests\Fields;


use PHPUnit\Framework\TestCase;
use TypeRocket\Elements\Fields\Items;
use TypeRocket\Models\WPPost;

class ItemsFieldTest extends TestCase
{
    public function testItemsField()
    {
        $field = 'tr_test_items';
        $id = 1;
        $m = (new WPPost)->find($id);
        $data = ['item 1', 'item 2'];
        add_post_meta($id, $field, $data, true);

        $text = (new Items($field, ['data-test' => 'X']))->setModel($m)->getString();

        // delete data before assert
        delete_post_meta($id, $field);

        $this->assertStringContainsString('name="tr['.$field.']" value="0" data-test="X"', $text);
        $this->assertStringContainsString('data-tr-name="tr['.$field.']"', $text);
        $this->assertStringContainsString('name="tr['.$field.'][]" value="'.$data[0].'"', $text);
        $this->assertStringContainsString('name="tr['.$field.'][]" value="'.$data[1].'"', $text);
    }
}