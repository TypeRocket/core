<?php
declare(strict_types=1);

namespace Forms;

use PHPUnit\Framework\TestCase;
use TypeRocket\Elements\BaseForm;
use TypeRocket\Models\WPComment;
use TypeRocket\Models\WPOption;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\WPUser;
use TypeRocket\Utility\DataCollection;

class FormTest extends TestCase
{

    public function testSimpleUpdatePostForm()
    {
        $form = new BaseForm('post', 'update', 1, WPPost::class);
        $title = $form->text('post_title')->getValue();
        $content = $form->textarea('post_content')->getValue();

        $this->assertStringContainsString('Hello', $title );
        $this->assertStringContainsString('Welcome', $content );
        $this->assertInstanceOf( BaseForm::class, $form);
        $this->assertTrue( $form->getAction() == 'update');
    }

    public function testSimpleUpdatePostFormShortShorthand()
    {
        $form = new BaseForm('post', 1);
        $title = $form->text('post_title')->getValue();
        $content = $form->textarea('post_content')->getValue();

        $this->assertStringContainsString('Hello', $title );
        $this->assertStringContainsString('Welcome', $content );
        $this->assertTrue( $form->getAction() == 'update');
        $this->assertInstanceOf( BaseForm::class, $form);
    }

    public function testSimpleUpdatePostFormPostCreate()
    {
        $form = new BaseForm('post', 'post');

        $this->assertTrue( $form->getAction() == 'create');
        $this->assertInstanceOf( BaseForm::class, $form);
    }

    public function testSimpleUpdatePostFormShortShorthandCreate()
    {
        $form = new BaseForm('post');

        $this->assertTrue( $form->getAction() == 'create');
        $this->assertInstanceOf( BaseForm::class, $form);
    }

    public function testSimpleUpdatePostFormUpdate()
    {
        $model = (new WPPost)->find(1);
        $form = new BaseForm($model);

        $this->assertTrue( $form->getAction() == 'update');
        $this->assertInstanceOf( BaseForm::class, $form);
    }

    public function testSimpleUpdatePostFormCreate()
    {
        $model = (new WPPost)->find(1);
        $form = new BaseForm($model, 'create');

        $this->assertTrue( $form->getAction() == 'create');
        $this->assertInstanceOf( BaseForm::class, $form);
    }

    public function testSimpleUpdatePostFormNoProps()
    {
        $form = new BaseForm(new WPPost);

        $this->assertTrue( $form->getAction() == 'create');
        $this->assertInstanceOf( BaseForm::class, $form);
    }

    public function testSimpleUpdatePostFormShort()
    {
        $form = new BaseForm(WPPost::class, 1);
        $title = $form->text('post_title')->getValue();
        $content = $form->textarea('post_content')->getValue();

        $this->assertStringContainsString('Hello', $title );
        $this->assertStringContainsString('Welcome', $content );
        $this->assertTrue( $form->getAction() == 'update');
        $this->assertInstanceOf( BaseForm::class, $form);
    }

    public function testSimpleMetaPostForm()
    {
        add_post_meta(1, 'deep_test', 'nested value', true);

        $form = new BaseForm('post', 'update', 1, WPPost::class);
        $value = $form->text('meta.Deep Test')->getValue();

        $this->assertTrue('nested value' == $value );
    }

    public function testFormCustomFormFieldData()
    {
        $data = [
            'meta' => ['deep_test' => 'nested value']
        ];
        $form = new BaseForm( new DataCollection($data) );
        $value = $form->text('meta.Deep Test')->getValue();

        $this->assertTrue('nested value' == $value );
        $this->assertTrue( $form->getAction() == 'update');
    }

    public function testFormArrayData()
    {
        $data = [
            'meta' => ['deep_test' => 'nested value']
        ];
        $form = new BaseForm( $data );
        $value = $form->text('meta.Deep Test')->getValue();

        $this->assertTrue('nested value' == $value );

        $this->assertTrue( $form->getAction() == 'update');
    }

    public function testUseURL()
    {
        $form = new BaseForm('post', 'update', 1, WPPost::class);
        $form->toUrl('/posts/create', 'delete');
        $value = $form->open();
        $needle = 'action="' . get_site_url(null, '/posts/create');
        $this->assertStringContainsString( $needle, $value );
        $this->assertStringContainsString( 'name="_method" value="DELETE"', $value );
    }

    public function testUseAbsURL()
    {
        $form = new BaseForm('post', 'update', 1, WPPost::class);
        $form->toUrl('http://example.com/posts/create', 'patch');
        $value = $form->open();
        $needle = 'action="http://example.com/posts/create';
        $this->assertStringContainsString( $needle, $value );
        $this->assertStringContainsString( 'name="_method" value="PATCH"', $value );
    }

    public function testUseAbsURLSecure()
    {
        $form = new BaseForm('post', 'update', 1, WPPost::class);
        $form->toUrl('https://example.com/posts/create', 'patch');
        $value = $form->open();
        $needle = 'action="https://example.com/posts/create';
        $this->assertStringContainsString( $needle, $value );
        $this->assertStringContainsString( 'name="_method" value="PATCH"', $value );
    }

    public function testQuickForm()
    {
        $form = new BaseForm('post', 1, null, WPPost::class);
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 1 );
        $this->assertTrue($form->getResource() == 'post' );
    }

    public function testBasicCreateForm()
    {
        $form = new BaseForm('post', 'create');
        $this->assertTrue($form->getAction() == 'create' );
        $this->assertTrue($form->getItemId() == null);
        $this->assertTrue($form->getResource() == 'post' );
    }

    public function testBasicDeleteForm()
    {
        $form = new BaseForm('post', 'delete', 33, WPPost::class);
        $this->assertTrue($form->getAction() == 'delete' );
        $this->assertTrue($form->getItemId() == 33 );
        $this->assertTrue($form->getResource() == 'post' );
    }

    public function testBasicUpdateForm()
    {
        $form = new BaseForm('post', 'update', 12, WPPost::class);
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 12 );
        $this->assertTrue($form->getResource() == 'post' );
    }

    public function testCommentUpdateForm()
    {
        $form = new BaseForm('comment', 'update', 12, WPComment::class);
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 12 );
        $this->assertTrue($form->getResource() == 'comment' );
    }

    public function testTermAutoUpdateForm()
    {
        $gt = $GLOBALS['taxonomy'] ?? null;
        $gi = $GLOBALS['tag_ID'] ?? null;

        $GLOBALS['taxonomy'] = 'category';
        $GLOBALS['tag_ID'] = 1;

        $form = new BaseForm();
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 1 );
        $this->assertTrue($form->getModel() instanceof WPTerm);
        $this->assertTrue($form->getResource() == 'category' );

        $GLOBALS['taxonomy'] = $gt;
        $GLOBALS['tag_ID'] = $gi;
    }

    public function testUndefinedTermAutoUpdateForm()
    {
        $gt = $GLOBALS['taxonomy'] ?? null;
        $gi = $GLOBALS['tag_ID'] ?? null;

        $GLOBALS['taxonomy'] = 'topic';
        $GLOBALS['tag_ID'] = 0;

        $form = new BaseForm();
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 0 );
        $this->assertTrue($form->getModel() instanceof WPTerm);
        $this->assertTrue($form->getResource() == 'topic' );

        $GLOBALS['taxonomy'] = $gt;
        $GLOBALS['tag_ID'] = $gi;
    }

    public function testPostAutoUpdateForm()
    {
        $p = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = get_post(1);

        $form = new BaseForm();
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getModel() instanceof WPPost);
        $this->assertTrue($form->getItemId() == 1 );
        $this->assertTrue($form->getResource() == 'post' );

        $GLOBALS['post'] = $p;
    }

    public function testCommentAutoUpdateForm()
    {
        $c = $GLOBALS['comment'] ?? null;
        $id = 1;
        $GLOBALS['comment'] = get_comment($id);

        $form = new BaseForm();
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getModel() instanceof WPComment);
        $this->assertTrue($form->getItemId() == 1 );
        $this->assertTrue($form->getResource() == 'comment' );

        $GLOBALS['comment'] = $c;
    }

    public function testPageAutoUpdateForm()
    {
        $p = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = get_post(2);

        $form = new BaseForm();
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 2 );
        $this->assertTrue($form->getModel() instanceof WPPost);
        $this->assertTrue($form->getResource() == 'page' );

        $GLOBALS['post'] = $p;
    }

    public function testUserAutoUpdateForm()
    {
        $u = $GLOBALS['user_id'] ?? null;
        $GLOBALS['user_id'] = 1;

        $form = new BaseForm();
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == 1 );
        $this->assertTrue($form->getModel() instanceof WPUser);
        $this->assertTrue($form->getResource() == 'user' );

        $GLOBALS['user_id'] = $u;
    }

    public function testOptionAutoUpdateForm()
    {
        // If globals are still set due to
        // fails tests above this will fail
        $form = new BaseForm();
        $this->assertTrue($form->getAction() == 'update' );
        $this->assertTrue($form->getItemId() == null );
        $this->assertTrue($form->getModel() instanceof WPOption);
        $this->assertTrue($form->getResource() == 'option' );
    }

    public function testHelperForm()
    {
        $form = \TypeRocket\Utility\Helper::form();
        $this->assertTrue($form instanceof BaseForm);
    }
}