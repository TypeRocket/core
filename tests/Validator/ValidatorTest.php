<?php
declare(strict_types=1);

namespace Validator;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;
use TypeRocket\Models\WPPost;
use TypeRocket\Utility\Validators\ValidatorRule;
use TypeRocket\Utility\Validator;
use TypeRocket\Utility\Validators\EmailValidator;

class ValidatorTest extends TestCase
{
    public function testValidatorRuleRequiredFieldsNotStrongComplexFail()
    {
        $fields['data'] = [
            'name' => [
                'first' => '',
                'last' => ''
            ],
            'email' => 'k@exampl.com',
        ];

        $validator = new Validator([
            'data' => 'required:only_subfields=name.first,name.last'
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(0, $passes);
    }

    public function testValidatorRuleRequiredFieldsNotStrongComplexPassWeak()
    {
        $fields['data'] = [
            'name' => [
                'first' => '',
                'last' => ''
            ],
            'email' => 'k@exampl.com',
        ];

        $validator = new Validator([
            'data' => '?required:only_subfields=name.first,name.last'
        ], $fields, null);

        $validator->validate();

        $passes = count($validator->getPasses());

        $this->assertEquals(1, $passes);
    }

    public function testValidatorRuleRequiredFieldsStrongFail()
    {
        $fields['data'] = [
            'name' => ' ',
            'email' => 'k@exampl.com',
        ];

        $validator = new Validator([
            'data' => 'required:only_subfields=name/strong'
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(0, $passes);
    }

    public function testValidatorRuleRequiredFieldsNotStrong()
    {
        $fields['data'] = [
            'name' => ' ',
            'email' => 'k@exampl.com',
            'null' => null,
        ];

        $validator = new Validator([
            'data' => 'required:only_subfields=name'
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(1, $passes);
    }

    public function testValidatorRule()
    {
        $fields['email'] = 'example@typerocket.com';

        $validator = new Validator([
            'email' => [ EmailValidator::new(), 'required' ]
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(2, $passes);
    }

    public function testValidatorRuleRequiredDeepEmptyArrayAllowZero()
    {
        $fields['data'] = [
            'name' => null,
            'email' => '',
            'string' => '0',
            'int' => 0,
        ];

        $validator = new Validator([
            'data' => ['required:allow_zero']
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(1, $passes);
    }

    public function testValidatorRuleRequiredDeepEmptyArrayAllowZeroStrong()
    {
        $fields['data'] = [
            'name' => null,
            'email' => '   ',
            'string' => '0 ',
            'int' => 0,
        ];

        $validator = new Validator([
            'data' => ['required:allow_zero/strong']
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(1, $passes);
    }

    public function testValidatorRuleRequiredDeepEmptyArrayNotZeroInt()
    {
        $fields['data'] = [
            'name' => null,
            'email' => '',
            'int' => 0,
        ];

        $validator = new Validator([
            'data' => ['required']
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(0, $passes);
    }

    public function testValidatorRuleRequiredDeepEmptyArrayNotZeroString()
    {
        $fields['data'] = [
            'name' => null,
            'email' => '',
            'string' => '0',
        ];

        $validator = new Validator([
            'data' => ['required']
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(0, $passes);
    }

    public function testValidatorRuleRequiredDeepEmptyArrayFail()
    {
        $fields['data'] = [
            'name' => null,
            'email' => '',
            'number' => [
                'key' => []
            ],
        ];

        $validator = new Validator([
            'data' => ['required']
        ], $fields, null, true);

        $passes = count($validator->getPasses());

        $this->assertEquals(0, $passes);
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
        $fields['person2'] = 'Kevin';

        $validator = new Validator([
            'email' => 'required|max:4',
            'email2' => 'required|max:4',
            'person' => 'max:4',
            'person2' => 'max:4',
        ], $fields, null, false);

        $validator->setErrorMessages([
            'person:max' => 'Custom Message',
            'person2:max' => ['full' => 'Person Custom Message', 'field' => 'Custom Message'],
            'email:required' => function($name, $type, $message, $matches, $error_field) {
                return [
                    'full' => $message . ' Callable',
                    'field' => $error_field . ' Callable',
                ];
            },
            'email2:required' => function($name, $type, $message)
            {
                return $message . ' Callable';
            }
        ])->validate();

        $errors = $validator->getErrors();
        $fields = $validator->getErrorFields();

        $this->assertEquals('Custom Message', $errors['person']);
        $this->assertEquals('Custom Message', $fields['person']);
        $this->assertEquals('Person Custom Message', $errors['person2']);
        $this->assertEquals('Custom Message', $fields['person2']);
        $this->assertEquals('<strong>"Email2"</strong> is required. Callable', $errors['email2']);
        $this->assertEquals('<strong>"Email2"</strong> is required. Callable', $errors['email2']);
        $this->assertEquals('<strong>"Email"</strong> is required. Callable', $errors['email']);
        $this->assertEquals('is required. Callable', $fields['email']);
    }

    public function testFirstErrorMessageShown()
    {
        $fields['person'] = ' ';

        $validator = Validator::new([
            'person' => '?required:strong|numeric',
        ], $fields, null, false)->validate(true);

        $errors = $validator->getErrors();
        $fields = $validator->getErrorFields();

        $this->assertEquals($errors['person'], '<strong>"Person"</strong> is required.');
        $this->assertEquals($fields['person'], 'is required.');
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

    public function testDeepEmailFieldFailsWithCustomLabel()
    {
        $fields['person']['email'] = 'example?typerocket.com';

        $validator = new Validator([
            'person.email' => 'email'
        ], $fields, null);

        $validator->setCallback('fieldLabel', function($fullDotPath, $obj, $value) {
            return $fullDotPath;
        });

        $validator->validate();

        $errorFull = $validator->getError('person.email');
        $errorField = $validator->getErrorField('person.email');

        $this->assertTrue( $validator->failed() );
        $this->assertTrue( $errorFull == 'person.email ' . $errorField);
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

    public function testSizeOptionalFailAndPass()
    {
        $fields['number'] = null;

        $validator = new Validator([
            'number' => '?size:2'
        ], $fields, null, true);


        $this->assertEquals(0, count($validator->getErrors()) );
        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testSizeOptionalFailAndPassWithBlankString()
    {
        $fields['number'] = '';

        $validator = new Validator([
            'number' => '?size:2'
        ], $fields, null, true);


        $this->assertEquals(0, count($validator->getErrors()) );
        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testSizeExactOptionalFailAndPass()
    {
        $fields['number'] = '121';

        $validator = new Validator([
            'number' => '?size:2|numeric'
        ], $fields, null, true);


        $this->assertEquals(1, count($validator->getErrors()) );
        $this->assertEquals(1, count($validator->getPasses()) );
    }

    public function testSizeExactOptionalFailAndPassFields()
    {
        $fields['data'] = [
            'age' => [
                'first' => 123,
                'last' => 456
            ],
            'email' => 'k@exampl.com',
        ];

        $validator = new Validator([
            'data' => 'numeric:only_subfields=age.first,age.last'
        ], $fields, null);

        $validator->validate();

        $passes = count($validator->getPasses());

        $this->assertEquals(1, $passes);
    }

    public function testSizeExactOptionalBothFailAndPass()
    {
        $fields['number'] = null;

        $validator = new Validator([
            'number' => '?size:2|?numeric'
        ], $fields, null, true);


        $this->assertEquals(0, count($validator->getErrors()) );
        $this->assertEquals(2, count($validator->getPasses()) );
    }

    public function testSizeExactFailAndPass()
    {
        $fields['number'] = '12';

        $validator = new Validator([
            'number' => 'size:2'
        ], $fields, null, true);


        $this->assertEquals(0, count($validator->getErrors()) );
        $this->assertEquals(1, count($validator->getPasses()) );
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