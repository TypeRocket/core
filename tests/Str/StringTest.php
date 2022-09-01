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

    public function testStartsBlank()
    {
        $this->assertTrue( Str::starts('', 'typerocket, is the name of the game.') );
    }

    public function testEnds()
    {
        $this->assertTrue( Str::ends('typerocket?', 'is the name of the game typerocket?') );
        $this->assertTrue( Str::ends('game ' . PHP_EOL, 'is the name of the game ' . PHP_EOL) );
    }

    public function testEndsBlank()
    {
        $this->assertTrue( Str::ends('', 'is the name of the game typerocket?') );
    }

    public function testContains()
    {
        $this->assertTrue( Str::contains('typerocket is!', 'What is the name of the game? typerocket is!') );
        $this->assertTrue( Str::contains('name of the game', 'What is the name of the game? typerocket is!') );
    }

    public function testContainsBlank()
    {
        $this->assertTrue( Str::contains('', 'What is the name of the game? typerocket is!') );
    }

    public function testExplodeRight()
    {
        $e = Str::explodeFromRight('.', 'one.two.three', 2);
        $this->assertTrue($e[0] === 'one.two');
        $this->assertTrue($e[1] === 'three');
    }

    public function testRemoveStartsWith()
    {
        $this->assertTrue( Str::trimStart('root-folder/new-path', 'root-folder') == '/new-path' );

        $root = trim('/root-folder/new-path/', '/');
        $trimmed = Str::trimStart('root-folder/new-path/nested',  $root);

        $this->assertTrue( ltrim( $trimmed, '/') == 'nested' );
    }

    public function testSnake()
    {
        $this->assertTrue( Str::snake('oneTwo') === 'one_two' );
        $this->assertTrue( Str::snake('game on') === 'game_on' );
    }

    public function testBlank()
    {
        $this->assertTrue( Str::blank('some value') === false );
        $this->assertTrue( Str::notBlank('some value') === true );

        $this->assertTrue( Str::blank('') === true );
        $this->assertTrue( Str::notBlank('') === false );

        $this->assertTrue( Str::blank(null) === true );
        $this->assertTrue( Str::notBlank(null) === false );

        $this->assertTrue( Str::blank([]) === false );
        $this->assertTrue( Str::notBlank([]) === true );
    }
}