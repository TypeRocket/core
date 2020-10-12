<?php
declare(strict_types=1);

namespace Validator;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Str;

class ViewsTest extends TestCase
{
    public function testViewToString()
    {
        $title = 'Test Title Here';
        $str = tr_view('master', compact('title'))->setContext('views')->toString();

        $this->assertTrue(Str::contains($title, $str));
    }

    public function testViewWithCache()
    {

        $title = 'Test Title Here';
        $str = tr_view('master', compact('title'))->cache('test.views', 20);

        $this->assertTrue(Str::contains($title, $str));
    }
}