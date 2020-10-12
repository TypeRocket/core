<?php
namespace TypeRocket\tests\Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Http\Request;

class CacheTest  extends TestCase
{

    public static $storedValue = null;

    public function testPutCache()
    {
        $value = self::$storedValue = time();
        $file = tr_cache()->put('kevin', $value, 10);
        $content = $file->readFirstCharactersTo(null, 10);
        $cached = unserialize($content);

        $this->assertTrue( $cached === $value );
    }

    public function testGetCache()
    {
        $content = tr_cache()->get('kevin');
        tr_cache()->remove('kevin');
        $hello = tr_cache()->get('kevin', 'hello');

        $this->assertTrue( $content === self::$storedValue );
        $this->assertTrue( $hello === 'hello' );
    }

    public function testOtherwiseCache()
    {
        $data = tr_cache()->getOtherwisePut('kevin', function(Request $request) {
            return $request;
        }, 10);

        $this->assertInstanceOf(Request::class, $data);
        $this->assertInstanceOf(Request::class, tr_cache()->get('kevin') );
    }

    public function testPutCacheLong()
    {
        tr_cache()->put('longterm', 'something', 60);
        sleep(2);
        $sec = tr_cache()->getSecondsRemaining('longterm');

        $this->assertTrue( $sec === 57 || $sec === 58 );
        $this->assertTrue( !tr_cache()->hasExpired('longterm') );
    }

    public function testGetCacheNamedFolder()
    {
        tr_cache('test')->put('kevin', 12345, 10);
        $content = tr_cache('test')->get('kevin');
        tr_cache('test')->remove('kevin');
        $hello = tr_cache('test')->get('kevin', 'hello');

        $this->assertTrue( $content === 12345 );
        $this->assertTrue( $hello === 'hello' );
    }

}