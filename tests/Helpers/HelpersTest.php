<?php
declare(strict_types=1);

class HelpersTest extends \PHPUnit\Framework\TestCase
{

    public function testPostsFieldGlobalHelper()
    {
        $cp = $GLOBALS['post'];
        $GLOBALS['post'] = get_post(1);

        $title = tr_post_field('post_title');

        $GLOBALS['post'] = $cp;

        $this->assertContains('Hello', $title);
    }

    public function testTermFieldHelper()
    {
        $tax = tr_term_field('taxonomy', 'category', 1);

        $this->assertTrue('category' == $tax);
    }

    public function testTermFieldWithIddHelper()
    {
        $tax = tr_term_field('taxonomy', null, 1);

        $this->assertTrue('category' == $tax);
    }

    public function testCommentFieldGlobalHelper()
    {
        $cc = $GLOBALS['comment'];
        $id = 1;
        $GLOBALS['comment'] = get_comment($id);

        $post_id = tr_comment_field('comment_post_id');
        $GLOBALS['comment'] = $cc;

        $this->assertTrue(1 == $post_id);
    }

    public function testCommentFieldNotGlobalHelper()
    {
        $post_id = tr_comment_field('comment_post_id', 1);

        $this->assertTrue(1 == $post_id);
    }

    public function testUserFieldGlobalPostHelper()
    {
        $cp = $GLOBALS['post'];
        $GLOBALS['post'] = get_post(1);

        $email = tr_user_field('user_email');

        $GLOBALS['post'] = $cp;

        $this->assertContains('@', $email);
    }

    public function testUserFieldGlobalUserIdHelper()
    {
        $cp = $GLOBALS['user_id'];
        $GLOBALS['user_id'] = 1;

        $email = tr_user_field('user_email');

        $GLOBALS['user_id'] = $cp;

        $this->assertContains('@', $email);
    }

    public function testCastingInt()
    {
        $types = [
            'int',
            'integer'
        ];

        $ints = [
            1,
            null,
            '1',
            'abc',
            0,
            '',
            33,
            '33',
            false,
            true,
            json_encode([]),
            serialize([]),
        ];

        $nulls = [
            [],
            new stdClass()
        ];

        foreach ($nulls as $v) {
            foreach($types as $t) {
                $this->assertTrue(tr_cast($v, $t) === null);
            }
        }

        foreach ($ints as $v) {
            foreach($types as $t) {
                $int = tr_cast($v, $t);
                $this->assertTrue(is_int($int));
            }
        }
    }

    public function testCastingFloat()
    {
        $types = [
            'real',
            'double',
            'float',
        ];

        $ints = [
            1,
            null,
            '1',
            'abc',
            0,
            33,
            '33',
            false,
            '',
            true,
            json_encode([]),
            serialize([]),
        ];

        $nulls = [
            [],
            new stdClass(),
        ];

        foreach ($nulls as $v) {
            foreach($types as $t) {
                $this->assertTrue(tr_cast($v, $t) === null);
            }
        }

        foreach ($ints as $v) {
            foreach($types as $t) {
                $int = tr_cast($v, $t);
                $this->assertTrue(is_float($int));
            }
        }
    }

    public function testCastingStr()
    {
        $types = [
            'str',
            'string'
        ];

        $values = [
            1,
            null,
            '1',
            0,
            33,
            '',
            '33',
            'abc',
            false,
            true,
            json_encode([]),
            serialize([]),
            [],
            new stdClass()
        ];

        foreach ($values as $v) {
            foreach($types as $t) {
                $str = tr_cast($v, $t);
                $this->assertTrue(is_string($str));
            }
        }
    }

    public function testCastingBool()
    {
        $types = [
            'bool',
            'boolean'
        ];

        $values = [
            1,
            0,
            33,
            '33',
            null,
            '1',
            'abc',
            '',
            false,
            true,
            json_encode([]),
            serialize([]),
            [],
            new stdClass()
        ];

        foreach ($values as $v) {
            foreach($types as $t) {
                $bool = tr_cast($v, $t);
                $this->assertTrue(is_bool($bool));
            }
        }
    }

    public function testCastingObject()
    {
        $types = [
            'object',
            'obj'
        ];

        $values = [
            json_encode([]),
            serialize([]),
            null,
            false,
            true,
            [],
            new stdClass()
        ];

        $same = [
            1,
            0,
            '',
            33,
            '33',
            '1',
            'abc',
        ];

        foreach ($values as $v) {
            foreach($types as $t) {
                $c = tr_cast($v, $t);
                $this->assertTrue(is_object($c));
            }
        }

        foreach ($same as $v) {
            foreach($types as $t) {
                $c = tr_cast($v, $t);
                $this->assertTrue($c === $v);
            }
        }
    }

    public function testCastingArray()
    {
        $types = [
            'array',
        ];

        $values = [
            json_encode([]),
            serialize([]),
            null,
            false,
            true,
            [],
            new stdClass()
        ];

        $same = [
            1,
            0,
            33,
            '',
            '33',
            '1',
            'abc',
        ];

        foreach ($values as $v) {
            foreach($types as $t) {
                $c = tr_cast($v, $t);
                $this->assertTrue(is_array($c));
            }
        }

        foreach ($same as $v) {
            foreach($types as $t) {
                $c = tr_cast($v, $t);
                $this->assertTrue($c === $v);
            }
        }
    }

    public function testCastingJson()
    {
        $types = [
            'json',
        ];

        $values = [
            json_encode([]),
            serialize([]),
            null,
            false,
            true,
            [],
            '',
            new stdClass(),
            1,
            0,
            33,
            '33',
            '1',
            'abc',
        ];

        foreach ($values as $v) {
            foreach($types as $t) {
                $c = tr_cast($v, $t);
                $this->assertTrue(tr_is_json($c));
            }
        }
    }

    public function testCastingSerial()
    {
        $types = [
            'serialize',
            'serial',
        ];

        $values = [
            json_encode([]),
            serialize([]),
            null,
            false,
            true,
            [],
            '',
            new stdClass(),
            1,
            0,
            33,
            '33',
            '1',
            'abc',
        ];

        foreach ($values as $v) {
            foreach($types as $t) {
                $c = tr_cast($v, $t);
                $this->assertTrue(is_serialized($c));
            }
        }
    }

}