<?php
declare(strict_types=1);

namespace Validator;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;
use TypeRocket\Models\WPPost;
use TypeRocket\Utility\Validator;

class ValidatorTest extends TestCase
{

    public function testEmailFieldPasses()
    {
        $fields['email'] = 'example@typerocket.com';

        $validator = new Validator([
            'email' => 'email'
        ], $fields);

        $this->assertTrue( $validator->passed() );
    }

    public function testDeepEmailFieldPasses()
    {
        $fields['person']['email'] = 'example@typerocket.com';

        $validator = new Validator([
            'person.email' => 'email'
        ], $fields);

        $this->assertTrue( $validator->passed() );
    }

    public function testDeepMultipleEmailsFieldPasses()
    {
        $fields['person'][1]['email'] = 'example@typerocket.com';
        $fields['person'][2]['email'] = 'example2.1@typerocket.com';

        $validator = new Validator([
            'person.*.email' => 'email'
        ], $fields);

        $this->assertEquals(2, count($validator->getPasses()) );
    }

    public function testDeepMultipleEmailsFieldFailing()
    {
        $fields['person'][1]['email'] = 'example@typerocket';
        $fields['person'][2]['email'] = 'e@example2.1@typerocket.com';
        $fields['person'][3]['email'] = 'example2.1typerocket.com';

        $validator = new Validator([
            'person.*.email' => 'email'
        ], $fields);

        $this->assertEquals(3, count($validator->getErrors()) );
    }

    public function testDeepCallbackFieldPassing()
    {
        $fields['person'][1]['email'] = 'example@typerocket';
        $fields['person'][2]['email'] = 'e@example2.1@typerocket.com';
        $fields['person'][3]['email'] = 'example2.1typerocket.com';

        function checkValidatorCallback($validator, $value, $field, $option2)
        {
            if( empty($value) ) {
               return  ['error' => $field . ' is bad'];
            }

            return ['success' => $field . ' is good'];
        }

        $validator = new Validator([
            'person.*.email' => 'callback:\Validator\checkValidatorCallback:option'
        ], $fields);

        $this->assertEquals(3, count($validator->getPasses()) );
    }

    public function testDeepCallbackFieldFailing()
    {
        $fields['person'][1]['email'] = '';
        $fields['person'][2]['email'] = '';
        $fields['person'][3]['email'] = '';

        function checkValidatorCallbackError($validator,  $value, $field, $option2)
        {
            if( empty($value) ) {
                return  ['error' => $field . ' is bad'];
            }

            return ['success' => $field . ' is good'];
        }

        $validator = new Validator([
            'person.*.email' => 'callback:\Validator\checkValidatorCallbackError:option'
        ], $fields);

        $this->assertEquals(3, count($validator->getErrors()) );
        $this->assertNotTrue( $validator->passed() );
        $this->assertNotTrue( $validator->passed() );
    }

    public function testMinFailing()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'person' => 'min:6'
        ], $fields);

        $this->assertEquals(1, count($validator->getErrors()) );
    }

    public function testMaxFailing()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'person' => 'max:4'
        ], $fields);

        $this->assertEquals(1, count($validator->getErrors()) );
    }

    public function testMinPassing()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'person' => 'min:5'
        ], $fields);

        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testMaxPassing()
    {
        $fields['person'] = 'Kevin';

        $validator = new Validator([
            'person' => 'max:5'
        ], $fields);

        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testSizePassing()
    {
        $fields['number'] = '12';

        $validator = new Validator([
            'number' => 'size:2'
        ], $fields);

        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testSizeFailing()
    {
        $fields['number'] = '123';

        $validator = new Validator([
            'number' => 'size:2'
        ], $fields);

        $this->assertEquals(1, count($validator->getErrors()) );
    }

    public function testUniqueFieldPasses()
    {
        $fields['option_name'] = 'mailserver_url';

        $result = (new Query())->table('wp_options')->where('option_name', $fields['option_name'])->first();

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options@option_id:' . $result['option_id']
        ], $fields);

        $this->assertTrue( $validator->passed() );
    }

    public function testUniqueFieldBasicFails()
    {
        $fields['option_name'] = 'mailserver_url';

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options'
        ], $fields);

        $this->assertTrue( ! $validator->passed() );
    }

    public function testUniqueFieldBasicPlusFails()
    {
        $fields['option_name'] = 'mailserver_url';

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options@option_name'
        ], $fields);

        $this->assertTrue( ! $validator->passed() );
    }

    public function testUniqueFieldBasicPasses()
    {
        $fields['option_name'] = 'this_is_not_an_option';

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options'
        ], $fields);

        $this->assertTrue( $validator->passed() );
    }

    public function testUniqueFieldFails()
    {
        $fields['option_name'] = 'mailserver_url';

        $validator = new Validator([
            'option_name' => 'unique:option_name:wp_options@option_id:0'
        ], $fields);

        $this->assertTrue( ! $validator->passed() );
    }

    public function testUniqueFieldWithModelFails()
    {
        $fields['title'] = 'Not an existing title';

        $validator = new Validator([
            'title' => 'unique:post_title'
        ], $fields, WPPost::class);

        $this->assertTrue( $validator->passed() );
    }

}