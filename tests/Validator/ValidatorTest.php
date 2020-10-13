<?php
declare(strict_types=1);

namespace Validator;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;
use TypeRocket\Models\WPPost;
use TypeRocket\Utility\ValidatorRule;
use TypeRocket\Utility\Validator;
use TypeRocket\Utility\Validators\EmailValidator;

class ValidatorTest extends TestCase
{

    public function testValidatorRule()
    {
        $fields['person'] = 'example@typerocket.com';

        $validator = new Validator([
            'person' => EmailValidator::new()
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testKeyRule()
    {
        $fields['id'] = 'id Here';

        $validator = new Validator([
            'id' => 'key'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getErrors()) );

        $fields['id'] = 'id_here';

        $validator = new Validator([
            'id' => 'key'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getPasses()) );

        $fields['id'] = 'id_Here';

        $validator = new Validator([
            'id' => 'key'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getErrors()) );
    }

    public function testDeepMultipleMaybe()
    {
        $fields['person'] = '';

        $validator = new Validator([
            'person.?.email' => 'email'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getPasses()) );
        $this->assertEquals(0, count($validator->getErrors()) );
    }

    public function testDeepMultipleMaybeFails()
    {
        $fields['person'][1]['email'] = 'example@typerocket.com';
        $fields['person'][2]['email'] = 'example2';

        $validator = new Validator([
            'person.?.email' => 'email'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getPasses()) );
        $this->assertEquals(1, count($validator->getErrors()) );
    }

    public function testSetMessage()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'email' => 'required|max:4',
            'person' => 'max:4',
        ], $fields, null, false);

        $validator->setErrorMessages([
            'person:max' => 'Custom Message',
            'email:required' => function($name, $type, $message) {
                return $message . ' Callable';
            }
        ])->validate();

        $errors = $validator->getErrors();

        $this->assertEquals($errors['person'], 'Custom Message');
        $this->assertEquals($errors['email'], '<strong>"Email"</strong> is required. Callable');
    }

    public function testDeepMultipleSetMessageRegex()
    {
        $fields['person'][1]['email'] = 'example@typerocket.com';
        $fields['person'][2]['email'] = 'example2';

        $validator = new Validator([
            'person.*.email' => 'email'
        ], $fields, null, false);

        $validator->setErrorMessages([
            'person\.(.*)\.(email)\:email' => function($name, $type, $message, $matches) {
                $m = $message;
                return $matches[0][1] . $matches[0][2];
            }
        ], true)->validate();

        $errors = $validator->getErrors();

        $this->assertEquals(1, count($validator->getPasses()) );
        $this->assertEquals(1, count($errors) );
        $this->assertEquals($errors['person.2.email'], '2email' );
    }

    public function testEmailFieldPasses()
    {
        $fields['email'] = 'example@typerocket.com';

        $validator = new Validator([
            'email' => 'email'
        ], $fields, null, true);

        $this->assertTrue( $validator->passed() );
    }

    public function testDeepEmailFieldPasses()
    {
        $fields['person']['email'] = 'example@typerocket.com';

        $validator = new Validator([
            'person.email' => 'email'
        ], $fields, null, true);

        $this->assertTrue( $validator->passed() );
    }

    public function testDeepMultipleEmailsFieldPasses()
    {
        $fields['person'][1]['email'] = 'example@typerocket.com';
        $fields['person'][2]['email'] = 'example2.1@typerocket.com';

        $validator = new Validator([
            'person.*.email' => 'email',
            'person.*.name' => 'required',
        ], $fields, null, true);

        $this->assertEquals(2, count($validator->getPasses()) );
        $this->assertEquals(2, count($validator->getErrors()) );
    }

    public function testDeepMultipleEmailsFieldFailing()
    {
        $fields['person'][1]['email'] = 'example@typerocket';
        $fields['person'][2]['email'] = 'e@example2.1@typerocket.com';
        $fields['person'][3]['email'] = 'example2.1typerocket.com';

        $validator = new Validator([
            'person.*.email' => 'email'
        ], $fields, null, true);

        $this->assertEquals(3, count($validator->getErrors()) );
    }

    public function testDeepCallbackFieldPassing()
    {
        $fields['person'][1]['email'] = 'example@typerocket';
        $fields['person'][2]['email'] = 'e@example2.1@typerocket.com';
        $fields['person'][3]['email'] = 'example2.1typerocket.com';

        function checkValidatorCallback($args)
        {
            /**
             * @var $option3
             * @var $option
             * @var $option2
             * @var $name
             * @var $field_name
             * @var $value
             * @var $type
             * @var Validator $validator
             */
            extract($args);

            $error = null;

            if( empty($value) ) {
                return $field_name . ' is bad';
            }

            return true;
        }

        $validator = new Validator([
            'person.*.email' => 'callback:\Validator\checkValidatorCallback:option'
        ], $fields, null, true);

        $this->assertEquals(3, count($validator->getPasses()) );
    }

    public function testDeepCallbackFieldFailing()
    {
        $fields['person'][1]['email'] = '';
        $fields['person'][2]['email'] = '';
        $fields['person'][3]['email'] = '';

        function checkValidatorCallbackError($args)
        {
            /**
             * @var $option3
             * @var $option
             * @var $option2
             * @var $name
             * @var $field_name
             * @var $value
             * @var $type
             * @var Validator $validator
             */
            extract($args);

            $error = null;

            if( empty($value) ) {
                return $field_name . ' is bad';
            }

            return true;
        }

        $validator = new Validator([
            'person.*.email' => 'callback:\Validator\checkValidatorCallbackError:option'
        ], $fields, null, true);

        $this->assertEquals(3, count($validator->getErrors()) );
        $this->assertNotTrue( $validator->passed() );
        $this->assertNotTrue( $validator->passed() );
    }

    public function testMinFailing()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'person' => 'min:6'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getErrors()) );
    }

    public function testMaxFailing()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'person' => 'max:4'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getErrors()) );
    }

    public function testMinPassing()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'person' => 'min:5'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testMaxPassing()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'person' => 'max:5'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testSizePassing()
    {
        $fields['number'] = '12';

        $validator = new Validator([
            'number' => 'size:2'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testSizeFailing()
    {
        $fields['number'] = '123';

        $validator = new Validator([
            'number' => 'size:2'
        ], $fields, null, true);

        $this->assertEquals(1, count($validator->getErrors()) );
    }

    public function testUniqueFieldPasses()
    {
        $fields['option_name'] = 'mailserver_url';

        $result = (new Query())->table('wp_options')->where('option_name', $fields['option_name'])->first();

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options@option_id:' . $result['option_id']
        ], $fields, null, true);

        $this->assertTrue( $validator->passed() );
    }

    public function testUniqueFieldBasicFails()
    {
        $fields['option_name'] = 'mailserver_url';

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options'
        ], $fields, null, true);

        $this->assertTrue( ! $validator->passed() );
    }

    public function testUniqueFieldBasicPlusFails()
    {
        $fields['option_name'] = 'mailserver_url';

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options@option_name'
        ], $fields, null, true);

        $this->assertTrue( ! $validator->passed() );
    }

    public function testUniqueFieldBasicPasses()
    {
        $fields['option_name'] = 'this_is_not_an_option';

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options'
        ], $fields, null, true);

        $this->assertTrue( $validator->passed() );
    }

    public function testUniqueFieldFails()
    {
        $fields['option_name'] = 'mailserver_url';

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options@option_id:0'
        ], $fields, null, true);

        $this->assertTrue( ! $validator->passed() );
    }

    public function testUniqueFieldWithModelFails()
    {
        $fields['title'] = 'Not an existing title';

        $validator = new Validator([
            'title' => 'unique:post_title'
        ], $fields, WPPost::class, true);

        $this->assertTrue( $validator->passed() );
    }

}