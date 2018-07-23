<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Models\Model;

class ModelTest extends TestCase
{

    public function testFillableMethods()
    {
        $passing = false;
        $model = new Model();

        $fields = [
            'post_title',
            'post_content',
        ];

        $model->setFillableFields($fields);
        $model->appendFillableField('post_excerpt');
        $model->removeFillableField('post_title');

        $expected = ['post_content', 'post_excerpt'];
        $fillable = array_values( $model->getFillableFields() );

        if( $fillable == $expected ) {
            $passing = true;
        }

        $this->assertTrue($passing);
    }

    public function testGuardMethods()
    {
        $passing = false;
        $model = new Model();

        $fields = [
            'post_title',
            'post_content',
        ];

        $model->setGuardFields($fields);
        $model->appendGuardField('post_excerpt');
        $model->removeGuardField('post_title');

        $expected = ['post_content', 'post_excerpt'];
        $guard = array_values( $model->getGuardFields() );

        if( $guard == $expected ) {
            $passing = true;
        }

        $this->assertTrue($passing);
    }

    public function testFormatFields()
    {
        $passing = false;
        $model = new Model();

        $fields = [
            'post_title' => 'intval',
            'post_content' => 'intval',
        ];

        $model->setFormatFields($fields);
        $model->appendFormatField('post_excerpt', 'intval');
        $model->removeFormatField('post_title');

        $expected =  array_keys(['post_content' => 'intval', 'post_excerpt' => 'intval']);
        $format = array_keys( $model->getFormatFields() );

        if( $format == $expected ) {
            $passing = true;
        }

        $this->assertTrue($passing);
    }

    public function testUnlockField()
    {
        $passing = false;
        $model = new Model();

        $model->appendFillableField('post_title');
        $model->appendGuardField('post_excerpt');
        $model->unlockField('post_excerpt');

        $expected =  ['post_title', 'post_excerpt'];
        $fillable = array_values( $model->getFillableFields() );

        if( $fillable == $expected ) {
            $passing = true;
        }

        $guards = $model->getGuardFields();

        $this->assertTrue($passing);
        $this->assertTrue( $guards == ['id'] );
    }
}