<?php
declare(strict_types=1);

namespace Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Utility\Sanitize;

class SanitizeTest extends TestCase
{
    public function testSanitizeDash()
    {
        $string = ' --"2_ _e\'\'X  AM!pl\'e-"-1_@';
        $sanitized = Sanitize::dash($string);
        $this->assertTrue( $sanitized == '-2-ex-ample-1-' );
    }

    public function testSanitizeUnderscore()
    {
        $string = ' --"2_ _e\'\'X  AM!pl\'e-"-1_@';
        $sanitized = Sanitize::underscore($string);
        $this->assertTrue( $sanitized == '_2_ex_ample_1_' );
    }

    public function testSanitizeUnderscoreKeepDots()
    {
        $string = ' --"..2_ ._e\'\'X  AM!pl\'e-"-1_@';
        $sanitized = Sanitize::underscore($string, true);
        $this->assertTrue( $sanitized == '_.2_._ex_ample_1_' );
    }

    public function testSanitizeEditor()
    {
        $content = '<p>Hi there <a target="_blank" href="#">link</a></p>';

        $sanitized = Sanitize::editor($content, true, false, [
            'p' => [],
            'a' => [
                'href' => true,
                'target' => true,
            ],
        ]);

        $this->assertTrue( $content === $sanitized );
    }
}