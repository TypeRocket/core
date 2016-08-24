<?php

class SanitizeTest extends PHPUnit_Framework_TestCase
{
    public function testSanitizeDash()
    {
        $string = ' --"2_ _e\'\'X  AM!pl\'e-"-1_@';
        $sanitized = \TypeRocket\Utility\Sanitize::dash($string);
        $this->assertTrue( $sanitized == '-2-ex-ample-1-' );
    }

    public function testSanitizeUnderscore()
    {
        $string = ' --"2_ _e\'\'X  AM!pl\'e-"-1_@';
        $sanitized = \TypeRocket\Utility\Sanitize::underscore($string);
        $this->assertTrue( $sanitized == '_2_ex_ample_1_' );
    }
}