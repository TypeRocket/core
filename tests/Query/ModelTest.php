<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Models\Model;

class ModelTest extends TestCase
{

    public function testModelPropertyMutation()
    {
        $class = new class extends Model {

            protected $properties = [
                'name' => 'kevin'
            ];

            public function getNameProperty($value) {
                return $value . ' dees';
            }
        };

        $name = $class->name;
        $this->assertTrue($name === 'kevin dees');
    }

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

    public function testSelectTableJoinWhere()
    {
        $model = new Model();
        $model->getQuery()->table('wp_posts');
        $compiled = (string)
        $model->where('ID', 1)
            ->where([
                [   // index name based lookup
                    'value' => 'meta_key',
                    'operator' => '=',
                    'column' => 'meta_key',
                ],
                'AND',
                [   // index based lookup
                    'column' => 'meta_value',
                    'operator' =>'like',
                    'value' => 'Hello%',
                ]
            ])
            ->join('wp_postmeta', 'wp_posts.ID', 'wp_postmeta.post_id')->getQuery();
        $sql = "SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id WHERE ID = 1 AND (  meta_key = 'meta_key' AND meta_value like 'Hello%' ) ";
        $this->assertTrue( $compiled == $sql);
    }

    public function testFindOrNew()
    {
        $model = new Model();
        $model->getQuery()->table('wp_posts')->run = false;
        $model->findFirstWhereOrNew('ID', 1); // returns a new item for test only
        $compiled = $model->getQuery()->lastCompiledSQL;

        $sql = "SELECT * FROM wp_posts WHERE ID = 1 LIMIT 1 OFFSET 0";
        $this->assertTrue( $compiled == $sql);
    }

    public function testFindOrNewCheckForNewModel()
    {
        $model = new Model();
        $model->getQuery()->table('wp_posts')->run = false;
        $new = $model->findFirstWhereOrNew('ID', 1); // returns a new item for test only

        $sql = "SELECT * FROM wp_posts WHERE ID = 1 LIMIT 1 OFFSET 0";
        $this->assertTrue( empty($new->post_title) );
    }

    public function testFindOrDie()
    {
        $model = new Model();
        $model->getQuery()->table('wp_posts');
        $found = $model->findFirstWhereOrDie('ID', 1); // returns a new item for test only
        $this->assertTrue( !empty($found->post_title) );
    }
}