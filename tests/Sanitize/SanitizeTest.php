<?php

class SanitizeTest extends PHPUnit_Framework_TestCase
{
    public function testSanitizeDash()
    {
        require BASE_WP;

        $string = 'exam!ple_-@';
        $sanitized = \TypeRocket\Utility\Sanitize::dash($string);
        $this->assertTrue( $sanitized == 'example--' );
    }

    public function testSanitizeUnderscore()
    {
        require BASE_WP;

        $string = 'exam!ple-_@';
        $sanitized = \TypeRocket\Utility\Sanitize::underscore($string);
        $this->assertTrue( $sanitized == 'example__' );
    }
}