<?php


namespace TypeRocket\tests\Http;


use PHPUnit\Framework\TestCase;
use TypeRocket\Http\CustomRequest;

class RequestTest extends TestCase
{

    public function testRequestUriMergeQuery()
    {
        $request = new CustomRequest([
            'host' => 'example.com',
            'protocol' => 'https',
            'uri' => '/help.php?page=1&links=exampleQuery.x',
        ]);

        $uri = $request->getModifiedUri([
            'page' => 99
        ]);

        $this->assertTrue($uri == 'https://example.com/help.php?page=99&links=exampleQuery.x');
    }

    public function testRequestUriMergeQueryNoQuery()
    {
        $request = new CustomRequest([
            'host' => 'example.com',
            'protocol' => 'https',
            'uri' => '/help.php',
        ]);

        $uri = $request->getModifiedUri([
            'page' => 99
        ]);

        $this->assertTrue($uri == 'https://example.com/help.php?page=99');
    }

    public function testRequestUriMergeQueryEmpty()
    {
        $request = new CustomRequest([
            'host' => 'example.com',
            'protocol' => 'https',
            'uri' => '/help.php',
        ]);

        $uri = $request->getModifiedUri();

        $this->assertTrue($uri == 'https://example.com/help.php');
    }

    public function testGetPathWithoutRoot()
    {
        $request = new CustomRequest([
            'path' => 'dev/aquapark/tr-api/rest/option/',
        ]);

        $uri = $request->getPathWithoutRoot("https://example.com/dev/aquapark");

        $this->assertTrue($uri == 'tr-api/rest/option/');

        $request = new CustomRequest([
            'path' => 'dev/aquapark/tr-api/rest/option/',
        ]);

        $uri = $request->getPathWithoutRoot("http://example.com/dev/aquapark");

        $this->assertTrue($uri == 'tr-api/rest/option/');

        $request = new CustomRequest([
            'path' => '/dev/aquapark/tr-api/rest/option/',
        ]);

        $uri = $request->getPathWithoutRoot("http://example.com/dev/aquapark");

        $this->assertTrue($uri == 'tr-api/rest/option/');
    }

}