<?php

class SanitizeTest extends PHPUnit_Framework_TestCase
{
    public function testSanitizeDash()
    {
        require BASE_WP;

        $string = '--2_ _"e\'\'X  AM!\'ple-1_@';
        $sanitized = \TypeRocket\Utility\Sanitize::dash($string);

        var_dump($string, $sanitized);

        $this->assertTrue( $sanitized == '-2-ex_ample-1-' );
    }

    public function testSanitizeUnderscore()
    {
        require BASE_WP;

        $string = '--"2_ _e\'\'X  AM!pl\'e--1_@';
        $sanitized = \TypeRocket\Utility\Sanitize::underscore($string);

        var_dump($string, $sanitized);

        $this->assertTrue( $sanitized == '_2_ex_ample_1_' );
    }
}