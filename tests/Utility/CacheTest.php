<?php
namespace TypeRocket\tests\Utility;

use PHPUnit\Framework\TestCase;
use TypeRocket\Http\Request;
use TypeRocket\Utility\PersistentCache;

class CacheTest  extends TestCase
{

    public static $storedValue = null;

    public function testPutCache()
    {
        $value = self::$storedValue = time();
        $file = PersistentCache::new()->put('kevin', $value, 10);
        $content = $file->readFirstCharactersTo(null, 10);
        $cached = unserialize($content);

        $this->assertTrue( $cached === $value );
    }

    public function testGetCache()
    {
        $content = PersistentCache::new()->get('kevin');
        PersistentCache::new()->remove('kevin');
        $hello = PersistentCache::new()->get('kevin', 'hello');

        $this->assertTrue( $content === self::$storedValue );
        $this->assertTrue( $hello === 'hello' );
    }

    public function testOtherwiseCache()
    {
        $data = PersistentCache::new()->getOtherwisePut('kevin', function(Request $request) {
            return $request;
        }, 10);

        $this->assertInstanceOf(Request::class, $data);
        $this->assertInstanceOf(Request::class, PersistentCache::new()->get('kevin') );
    }

    public function testPutCacheLong()
    {
        PersistentCache::new()->put('longterm', 'something', 60);
        sleep(2);
        $sec = PersistentCache::new()->getSecondsRemaining('longterm');

        $this->assertTrue( $sec === 57 || $sec === 58 );
        $this->assertTrue( !PersistentCache::new()->hasExpired('longterm') );
    }

    public function testGetCacheNamedFolder()
    {
        PersistentCache::new('test')->put('kevin', 12345, 10);
        $content = PersistentCache::new('test')->get('kevin');
        PersistentCache::new('test')->remove('kevin');
        $hello = PersistentCache::new('test')->get('kevin', 'hello');

        $this->assertTrue( $content === 12345 );
        $this->assertTrue( $hello === 'hello' );
    }

}