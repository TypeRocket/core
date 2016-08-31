<?php
namespace Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{

    public function testEmailFieldPasses()
    {
        $fields['email'] = 'example@typerocket.com';

        $validator = new \TypeRocket\Utility\Validator([
            'email' => 'email'
        ], $fields);

        $this->assertTrue( $validator->passed() );
    }

    public function testDeepEmailFieldPasses()
    {
        $fields['person']['email'] = 'example@typerocket.com';

        $validator = new \TypeRocket\Utility\Validator([
            'person.email' => 'email'
        ], $fields);

        $this->assertTrue( $validator->passed() );
    }

    public function testDeepMultipleEmailsFieldPasses()
    {
        $fields['person'][1]['email'] = 'example@typerocket.com';
        $fields['person'][2]['email'] = 'example2.1@typerocket.com';

        $validator = new \TypeRocket\Utility\Validator([
            'person.*.email' => 'email'
        ], $fields);

        $this->assertEquals(2, count($validator->getPasses()) );
    }

    public function testDeepMultipleEmailsFieldFailing()
    {
        $fields['person'][1]['email'] = 'example@typerocket';
        $fields['person'][2]['email'] = 'e@example2.1@typerocket.com';
        $fields['person'][3]['email'] = 'example2.1typerocket.com';

        $validator = new \TypeRocket\Utility\Validator([
            'person.*.email' => 'email'
        ], $fields);

        $this->assertEquals(3, count($validator->getErrors()) );
    }

}