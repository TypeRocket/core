<?php
declare(strict_types=1);

namespace Forms;

use PHPUnit\Framework\TestCase;
use TypeRocket\Elements\Form;
use TypeRocket\Models\WPPost;

class FormTest extends TestCase
{

    public function testSimpleUpdatePostForm()
    {
        $form = new Form('post', 'update', 1, WPPost::class);
        $title = $form->text('post_title')->getValue();
        $content = $form->textarea('post_content')->getValue();

        $this->assertContains('Hello', $title );
        $this->assertContains('Welcome', $content );
        $this->assertInstanceOf( Form::class, $form);
    }

    public function testUseURL()
    {
        $form = new Form('post', 'update', 1, WPPost::class);
        $form->useUrl('delete', '/posts/create');
        $value = $form->open();
        $needle = 'action="' . home_url('/posts/create/');
        $this->assertContains( $needle, $value );
        $this->assertContains( 'name="_method" value="DELETE"', $value );
    }

    public function testQuickForm()
    {
        $form = new Form('post', 1, null, WPPost::class);
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 1 );
        $this->assertTrue($form->getResource() == 'post' );
    }

    public function testBasicCreateForm()
    {
        $form = new Form('post', 'create');
        $this->assertTrue($form->getAction() == 'create' );
        $this->assertTrue($form->getItemId() == null);
        $this->assertTrue($form->getResource() == 'post' );
    }

    public function testBasicDeleteForm()
    {
        $form = new Form('post', 'delete', 33, WPPost::class);
        $this->assertTrue($form->getAction() == 'delete' );
        $this->assertTrue($form->getItemId() == 33 );
        $this->assertTrue($form->getResource() == 'post' );
    }

    public function testBasicUpdateForm()
    {
        $form = new Form('post', 'update', 12, WPPost::class);
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 12 );
        $this->assertTrue($form->getResource() == 'post' );
    }

    public function testHelperForm()
    {
        $form = tr_form();
        $this->assertTrue($form instanceof Form);
    }
}