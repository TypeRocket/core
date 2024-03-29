<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPPost;
use TypeRocket\Utility\QueryCaster;


class WhereTest extends TestCase
{

    public function testComposeWhereSqlSimple()
    {
        $query = new \TypeRocket\Database\Query();
        $sql = ' `wp_posts`.`ID` = 1 AND `wp_posts`.`ID` = \'2\'';
        $composed = $query->composeWhereSql([
            [
                'column' => 'wp_posts.ID',
                'operator' => '=',
                'value' => 1
            ],
            'AND',
            [
                'column' => 'wp_posts.ID',
                'operator' => '=',
                'value' => '2'
            ]
        ]);

        $this->assertTrue( $composed == $sql);
    }

    public function testComposeWhereSqlGrouped()
    {
        $query = new \TypeRocket\Database\Query();
        $sql = ' (  `wp_posts`.`ID` = 1 OR `wp_posts`.`ID` = \'2\' ) ';
        $composed = $query->composeWhereSql([[
            [
                'column' => 'wp_posts.ID',
                'operator' => '=',
                'value' => 1
            ],
            'OR',
            [
                'column' => 'wp_posts.ID',
                'operator' => '=',
                'value' => '2'
            ]
        ]]);

        $this->assertTrue( $composed == $sql);
    }

    public function testWhereUsingArray()
    {
        $query = new \TypeRocket\Database\Query('wp_posts');
        $sql = 'SELECT * FROM `wp_posts` WHERE `wp_posts`.`ID` = 1';

        $where = [
            'column' => 'wp_posts.ID',
            'operator' => '=',
            'value' => 1
        ];

        $composed = (string) $query->where($where);

        $this->assertTrue( $composed == $sql);
    }

    public function testWhereGrouped()
    {
        $query = new \TypeRocket\Database\Query('wp_posts');
        $sql = 'SELECT * FROM `wp_posts` WHERE (  `wp_posts`.`ID` = 1 && `wp_posts`.`ID` = 1 ) ';

        $where = [
            'column' => 'wp_posts.ID',
            'operator' => '=',
            'value' => 1
        ];

        $composed = (string) $query->where([$where, '&&', $where]);

        $this->assertTrue( $composed == $sql);
    }

    public function testComposeWhereSqlGroupedDeep()
    {
        $query = new \TypeRocket\Database\Query();
        $sql = ' (  `wp_posts`.`ID` = 1 OR `wp_posts`.`ID` = \'2\' )  OR (  `wp_posts`.`ID` = 1 OR `wp_posts`.`ID` = \'2\' ) ';

        $where = [
            [
                'column' => 'wp_posts.ID',
                'operator' => '=',
                'value' => 1
            ],
            'OR',
            [
                'column' => 'wp_posts.ID',
                'operator' => '=',
                'value' => '2'
            ]
        ];

        $composed = $query->composeWhereSql([$where, 'OR', $where]);

        $this->assertTrue( $composed == $sql);
    }

    public function testComposeWhereSqlGroupedDeepToString()
    {
        $query = new \TypeRocket\Database\Query('wp_posts');
        $sql = 'SELECT * FROM `wp_posts` WHERE (  (  `wp_posts`.`ID` = 1 OR `wp_posts`.`ID` = \'2\' )  OR (  `wp_posts`.`ID` = 1 OR `wp_posts`.`ID` = \'2\' )  ) ';

        $where = [
            [
                'column' => 'wp_posts.ID',
                'operator' => '=',
                'value' => 1
            ],
            'OR',
            [
                'column' => 'wp_posts.ID',
                'operator' => '=',
                'value' => '2'
            ]
        ];

        $composed = (string) $query->where([$where, 'OR', $where]);

        $this->assertTrue( $composed == $sql);
    }

    public function testRawWhere()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->where('ID', 1)
            ->appendRawWhere('AND', "post_status = 'publish'")
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `post_title`='My Title' WHERE `ID` = 1 AND post_status = 'publish'";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testOnlyRawWhere()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->appendRawWhere('AND', "post_status = 'publish'")
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `post_title`='My Title' WHERE post_status = 'publish'";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testMultipleRawWhere()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->appendRawWhere('AND', "`post_status` = 'publish'")
            ->appendRawWhere('OR', "(post_title = 'Hello World!' AND ID = 1)")
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `post_title`='My Title' WHERE `post_status` = 'publish' OR (post_title = 'Hello World!' AND ID = 1)";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testMultipleRawWhereNullFirst()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->appendRawWhere(null, "`post_status` = 'publish'")
            ->appendRawWhere('OR', "(post_title = 'Hello World!' AND ID = 1)")
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `post_title`='My Title' WHERE `post_status` = 'publish' OR (post_title = 'Hello World!' AND ID = 1)";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testMultipleRawWhereNullBoth()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->appendRawWhere(null, "post_status = 'publish'")
            ->appendRawWhere(null, "AND (post_title = 'Hello World!' AND ID = 1)")
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `post_title`='My Title' WHERE post_status = 'publish'  AND (post_title = 'Hello World!' AND ID = 1)";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testWhereResetBefore()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->where('ID', 1)
            ->appendRawWhere('AND', "post_status = 'publish'")
            ->removeWhere()
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `post_title`='My Title'";
        $last = $query->lastCompiledSQL;
        $this->assertTrue( $last == $sql);
    }

    public function testWhereNulls()
    {
        $query = new \TypeRocket\Database\Query('wp_posts', null, 'ID');
        $compiled = (string) $query
            ->where('ID', null)
            ->where('ID', null)
            ->where('ID', 0)
            ->where('ID', '!=', NULL)
            ->where('ID', '!=', "\0")
            ->where('ID', '!=', 0)
            ->where('ID', '=', null);

        $sql = "SELECT * FROM `wp_posts` WHERE `ID` IS NULL AND `ID` IS NULL AND `ID` = 0 AND `ID` IS NOT NULL AND `ID` != '\\0' AND `ID` != 0 AND `ID` IS NULL";
        $this->assertTrue( $compiled == $sql);
    }

    public function testWhereDeleteViaLoopHasIdColumn()
    {
        $data = [];
        $range = range(1,5);
        foreach ($range as $num) {
            $data[] = [
                'post_title' => "{$num} Test",
                'post_name' => "{$num}-test",
                'post_status' => 'draft'
            ];
        }

        $posts = [];
        foreach ($data as $post) {
            $new_post = WPPost::new('test')->create($post);
            $this->assertTrue( $new_post instanceof WPPost);
            $this->assertTrue( $new_post->post_type === 'test');
            $posts[$new_post->getID()] = $new_post;
        }
        ksort($posts);

        $model_query = (new class extends Model {
            protected $idColumn = 'ID';
            protected $table = 'wp_posts';
        });

        $query_posts = $model_query->where('post_type', 'test')->get();

        foreach ($query_posts as $query_post) {
            $model = $query_post;
            $model_query = $model->getQuery();
            $model->delete();
            $sql = $model_query->lastCompiledSQL ?? '';
            $this->assertStringContainsString('DELETE FROM `wp_posts` WHERE `wp_posts`.`ID` =', $sql);
        }
    }

    public function testWhereDeleteSingleHasIdColumn()
    {
        $data = [
            'post_title' => "100 Test",
            'post_name' => "100-test",
            'post_status' => 'draft'
        ];

        $new_post = WPPost::new('test')->create($data);
        $this->assertTrue( $new_post instanceof WPPost);
        $this->assertTrue( $new_post->post_type === 'test');

        $model_query = (new class extends Model {
            protected $idColumn = 'ID';
            protected $table = 'wp_posts';
        });

        $query_post = $model_query->find($new_post->getID());

        $model = $query_post;
        $model_query = $model->getQuery();
        $model->delete();
        $model_id = $model->getID();
        $sql = $model_query->lastCompiledSQL ?? '';
        $this->assertStringContainsString("DELETE FROM `wp_posts` WHERE `wp_posts`.`ID` = '{$model_id}' AND `wp_posts`.`ID` = '{$model_id}'", $sql);
    }

    public function testWhereResetAfter()
    {
        $query = new \TypeRocket\Database\Query();
        $query->table('wp_posts');
        $query->idColumn = 'ID';
        $query->run = false;
        $query
            ->where('ID', 1)
            ->update(['post_title' => 'My Title']);
        $sql = "UPDATE `wp_posts` SET `post_title`='My Title' WHERE `ID` = 1";
        $last_where = $query->lastCompiledSQL;
        $this->assertTrue( $last_where == $sql);


        $query->removeWhere()->update(['post_title' => 'My Title']);
        $sql_reset = "UPDATE `wp_posts` SET `post_title`='My Title'";
        $last_reset = $query->lastCompiledSQL;
        $this->assertTrue( $last_reset == $sql_reset);
    }
}