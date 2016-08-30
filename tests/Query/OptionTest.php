<?php
namespace Query;

class OptionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $option = new \TypeRocket\Models\WPOption();

        $data = [
            'typerocket_test_create' => 'About \'ok\' \TypeRocket\Name Code',
        ];

        $option->create($data);
        $content = get_option('typerocket_test_create');
        delete_option('typerocket_test_create');

        $this->assertTrue($content == $data['typerocket_test_create']);
    }
}