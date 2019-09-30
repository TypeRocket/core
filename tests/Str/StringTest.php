<?php
declare(strict_types=1);

namespace Str;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Str;

class StringTest extends TestCase
{
    public function testStarts()
    {
        $this->assertTrue( Str::starts('typerocket', 'typerocket, is the name of the game.') );
    }

    public function testEnds()
    {
        $this->assertTrue( Str::ends('typerocket?', 'is the name of the game typerocket?') );
    }

    public function testContains()
    {
        $this->assertTrue( Str::contains('typerocket is!', 'What is the name of the game? typerocket is!') );
    }

    public function testRemoveStartsWith()
    {
        $this->assertTrue( Str::trimStart('root-folder/new-path', 'root-folder') == '/new-path' );

        $root = trim('/root-folder/new-path/', '/');
        $trimmed = Str::trimStart('root-folder/new-path/nested',  $root);

        $this->assertTrue( ltrim( $trimmed, '/') == 'nested' );
    }
}