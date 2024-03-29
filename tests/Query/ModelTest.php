<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPUser;

class ModelTest extends TestCase
{

    public function testNewModelStatic()
    {
        $post = Model::new();

        $this->assertInstanceOf(Model::class, $post);
    }

    public function testSaveAndGetWhereCreate()
    {
        $post = WPPost::new();
        $post->post_title = 'saveAndGet';
        $post = $post->saveAndGet();

        $this->assertInstanceOf(WPPost::class, $post);
        $this->assertTrue($post->getID() > 0 && is_numeric($post->getID()));

        $deleted = $post->deleteForever();
        $this->assertTrue($deleted instanceof WPPost);
    }

    public function testSaveAndGetWhereUpdate()
    {
        $post = WPPost::new()->findById(1);
        $title = $post->post_title;
        $id = $post->getID();
        $post->post_title = 'saveAndGet';
        $post = $post->saveAndGet();

        $this->assertInstanceOf(WPPost::class, $post);
        $this->assertTrue($post->getID() > 0 && is_numeric($post->getID()) && $id === $post->getID());
        $this->assertTrue($post->post_title === 'saveAndGet');

        $wpPost = $post->update([
            'post_title' => $title
        ]);

        $this->assertTrue($wpPost->post_title === $title);
    }

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
        $sql = "SELECT DISTINCT `wp_posts`.* FROM `wp_posts` INNER JOIN `wp_postmeta` ON `wp_posts`.`ID` = `wp_postmeta`.`post_id` WHERE `ID` = 1 AND (  `meta_key` = 'meta_key' AND `meta_value` like 'Hello%' ) ";
        $this->assertTrue( $compiled == $sql);
    }

    public function testFindOrNew()
    {
        $model = new Model();
        $model->getQuery()->table('wp_posts')->run = false;
        $model->findFirstWhereOrNew('ID', 1); // returns a new item for test only
        $compiled = $model->getQuery()->lastCompiledSQL;

        $sql = "SELECT * FROM `wp_posts` WHERE `ID` = 1 LIMIT 1 OFFSET 0";
        $this->assertTrue( $compiled == $sql);
    }

    public function testFindOrNewCheckForNewModel()
    {
        $model = new Model();
        $model->getQuery()->table('wp_posts')->run = false;
        $new = $model->findFirstWhereOrNew('ID', 1); // returns a new item for test only

        $sql = "SELECT * FROM `wp_posts` WHERE `ID` = 1 LIMIT 1 OFFSET 0";
        $this->assertTrue( empty($new->post_title) );
        $this->assertTrue( $sql === $model->getQuery()->lastCompiledSQL );
    }

    public function testFindOrDie()
    {
        $model = new Model();
        $model->getQuery()->table('wp_posts');
        $found = $model->findFirstWhereOrDie('ID', 1); // returns a new item for test only
        $this->assertTrue( !empty($found->post_title) );
    }

    public function testUpdate()
    {
        $model = new class extends Model {
            protected $table = 'users';
            protected $properties = [
                'id' => 1,
                'name' => 'kevin'
            ];
            protected $propertiesUnaltered = [
                'id' => 1,
                'name' => 'kevin'
            ];
        };
        $model->getQuery()->table('users')->run = false;

        $model->name = 'kevin dees';
        $model->update();

        $actual = $model->getQuery()->lastCompiledSQL;
        $expected = "UPDATE `users` SET `name`='kevin dees' WHERE `id` = 1";
        $this->assertEquals($expected, $actual);
    }

    public function testUpdateAddArrayReplaceRecursiveKey()
    {
        $model = new class extends Model {
            protected $table = 'users';
            protected $properties = [
                'id' => 1,
                'name' => ['kevin','dees'],
                'job' => ['dev'],
            ];

            protected $propertiesUnaltered = [
                'id' => 1,
                'name' => ['kevin','dees'],
                'job' => ['dev'],
            ];
        };

        $model->getQuery()->table('users')->run = false;
        $model->setArrayReplaceRecursiveKey('name', function($new, $current, $key) {
            unset($new['z']);
            return $new;
        });

        $model->setArrayReplaceRecursiveKey('job', function($new, $current, $key) {
            unset($new['z']);
            return $new;
        });

        $model->job = [];
        $model->update(['name' => [0 =>'jim', 'a' => 'dev', 'z' => null]]);

        $actual = $model->getQuery()->lastCompiledSQL;
        $expected = 'UPDATE `users` SET `job`=\'a:1:{i:0;s:3:\\"dev\\";}\', `name`=\'a:3:{i:0;s:3:\\"jim\\";s:1:\\"a\\";s:3:\\"dev\\";i:1;s:4:\\"dees\\";}\' WHERE `id` = 1';
        $this->assertEquals($expected, $actual);
    }

    public function testUpdateAddArrayReplaceRecursiveKeyStops()
    {
        $model = new class extends Model {
            protected $table = 'users';
            protected $properties = [
                'id' => 1,
                'list' => ['list' => [1,2]],
            ];

            protected $propertiesUnaltered = [
                'id' => 1,
                'list' => ['list' => [1,2]],
            ];
        };

        $model->getQuery()->table('users')->run = false;
        $model->setArrayReplaceRecursiveStops('list', ['list']);

        $model->list = ['list' => [1 => 3] ];
        $model->update();

        $actual = $model->getQuery()->lastCompiledSQL;
        $expected = 'UPDATE `users` SET `list`=\'a:1:{s:4:\\"list\\";a:1:{i:1;i:3;}}\' WHERE `id` = 1';
        $this->assertEquals($expected, $actual);
    }

    public function testForZeroValue()
    {
        $model = WPPost::new()->first();
        update_post_meta($model->getID(), 'zero_value_field', '0');
        update_post_meta($model->getID(), 'zero_value_field_int', 0);
        update_post_meta($model->getID(), 'zero_value_field_null', null);
        update_post_meta($model->getID(), 'zero_value_field_array', []);

        $model->load('meta');

        delete_post_meta($model->getID(), 'zero_value_field');
        delete_post_meta($model->getID(), 'zero_value_field_int');
        delete_post_meta($model->getID(), 'zero_value_field_null');
        delete_post_meta($model->getID(), 'zero_value_field_array');

        $this->assertTrue($model->meta->zero_value_field === '0');
        $this->assertTrue($model->meta->zero_value_field_int === '0');
        $this->assertTrue($model->meta->zero_value_field_array === []);
        $this->assertTrue($model->meta->zero_value_field_null === null);
    }

    public function testForUnalteredData()
    {
        $model = WPUser::new()->first();
        $unaltered = $model->getPropertiesUnaltered();
        $this->assertTrue(!empty($unaltered['ID']));
    }
}