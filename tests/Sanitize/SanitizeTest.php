<?php

class SanitizeTest extends PHPUnit_Framework_TestCase
{
    public function testSanitizeDash()
    {
        require BASE_WP;

        $string = '--2__"exam!ple-1_@';
        $sanitized = \TypeRocket\Utility\Sanitize::dash($string);

        var_dump($string, $sanitized);

        $this->assertTrue( $sanitized == '-2-example-1-' );
    }

    public function testSanitizeUnderscore()
    {
        require BASE_WP;

        $string = '--"2__exam!ple--1_@';
        $sanitized = \TypeRocket\Utility\Sanitize::underscore($string);

        var_dump($string, $sanitized);

        $this->assertTrue( $sanitized == '_2_example_1_' );
    }
}