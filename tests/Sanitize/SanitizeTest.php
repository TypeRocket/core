<?php

class SanitizeTest extends PHPUnit_Framework_TestCase
{
    public function testSanitizeDash()
    {
        require BASE_WP;

        $string = '--2-"exam!ple_@';
        $sanitized = \TypeRocket\Utility\Sanitize::dash($string);

        var_dump($string, $sanitized);

        $this->assertTrue( $sanitized == '-2-example-' );
    }

    public function testSanitizeUnderscore()
    {
        require BASE_WP;

        $string = '--"exam!ple--1_@';
        $sanitized = \TypeRocket\Utility\Sanitize::underscore($string);

        var_dump($string, $sanitized);

        $this->assertTrue( $sanitized == '_example_1_' );
    }
}