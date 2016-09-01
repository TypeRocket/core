<?php

namespace Forms;

use TypeRocket\Elements\Form;
use TypeRocket\Models\WPPost;

class FormTest extends \PHPUnit_Framework_TestCase
{

    public function testSimpleUpdatePostForm()
    {
        $form = new Form('post', 'update', 1);
        $form->setModel( WPPost::class );
        $title = $form->text('post_title')->getValue();
        $content = $form->textarea('post_content')->getValue();

        $this->assertContains('Hello', $title );
        $this->assertContains('Welcome', $content );
        $this->assertInstanceOf( Form::class, $form);
    }
}