<?php

class SanitizeTest extends PHPUnit_Framework_TestCase
{
    public function testEmailFieldPasses()
    {
        require BASE_WP;

        $string = 'exam!ple@';
        $sanitized = \TypeRocket\Utility\Sanitize::dash($string);
        var_dump($sanitized, $string);

        $this->assertTrue( $sanitized == 'example' );
    }
}