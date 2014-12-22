<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;

/**
 * @group DCOM-101
 */
class FilesystemCacheTest extends BaseFileCacheTest
{
    public function testLifetime()
    {
        $cache = $this->_getCacheDriver();

        // Test save
        $cache->save('test_key', 'testing this out', 10);

        // Test contains to test that save() worked
        $this->assertTrue($cache->contains('test_key'));

        // Test fetch
        $this->assertEquals('testing this out', $cache->fetch('test_key'));

        // access private methods
        $getFilename        = new \ReflectionMethod($cache, 'getFilename');
        $getNamespacedId    = new \ReflectionMethod($cache, 'getNamespacedId');

        $getFilename->setAccessible(true);
        $getNamespacedId->setAccessible(true);

        $id         = $getNamespacedId->invoke($cache, 'test_key');
        $filename   = $getFilename->invoke($cache, $id);

        $data       = '';
        $lifetime   = 0;
        $resource   = fopen($filename, "r");

        if (false !== ($line = fgets($resource))) {
            $lifetime = (integer) $line;
        }

        while (false !== ($line = fgets($resource))) {
            $data .= $line;
        }

        $this->assertNotEquals(0, $lifetime, "previous lifetime could not be loaded");

        // update lifetime
        $lifetime = $lifetime - 20;
        file_put_contents($filename, $lifetime . PHP_EOL . $data);

        // test expired data
        $this->assertFalse($cache->contains('test_key'));
        $this->assertFalse($cache->fetch('test_key'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDirectoryModeAsNonIntThrows()
    {
        $cache = $this->_getCacheDriver();

        // This may look right, but if it is cast to (int) it will be 
        // (dec)755 instead of (oct)755, or 0755.  This is not what you want.
        $cache->setDirectoryMode('0775');
    }

    public function testSetDirectorySpreadChars()
    {
        $cache = $this->_getCacheDriver();
        $cache->setDirectorySpreadChars(3);

        // Test save
        $cache->save('test_key', 'testing this out', 10);

        // access private methods
        $getFilename        = new \ReflectionMethod($cache, 'getFilename');
        $getNamespacedId    = new \ReflectionMethod($cache, 'getNamespacedId');

        $getFilename->setAccessible(true);
        $getNamespacedId->setAccessible(true);

        $id         = $getNamespacedId->invoke($cache, 'test_key');
        $filename   = $getFilename->invoke($cache, $id);

        $path = trim(str_replace($this->directory, '', $filename), DIRECTORY_SEPARATOR);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        array_pop($parts);

        $this->assertCount(22, $parts);
    }

    public function testSetHasher()
    {
        $cache = $this->_getCacheDriver();
        $cache->setDirectorySpreadChars(2);

        // Test save
        $cache->save('test_key', 'testing this out', 10);

        // access private methods
        $getFilename        = new \ReflectionMethod($cache, 'getFilename');
        $getNamespacedId    = new \ReflectionMethod($cache, 'getNamespacedId');

        $getFilename->setAccessible(true);
        $getNamespacedId->setAccessible(true);

        $id         = $getNamespacedId->invoke($cache, 'test_key');
        $filename   = $getFilename->invoke($cache, $id);

        $path = trim(str_replace($this->directory, '', $filename), DIRECTORY_SEPARATOR);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        array_pop($parts);

        // 2-char spread and sha256 hash produce 32 dirs deep
        $this->assertCount(32, $parts);

        $cache->setHasher('md5');

        // Test save
        $cache->save('test_key', 'testing this out', 10);

        // access private methods
        $getFilename        = new \ReflectionMethod($cache, 'getFilename');
        $getNamespacedId    = new \ReflectionMethod($cache, 'getNamespacedId');

        $getFilename->setAccessible(true);
        $getNamespacedId->setAccessible(true);

        $id         = $getNamespacedId->invoke($cache, 'test_key');
        $filename   = $getFilename->invoke($cache, $id);

        $path = trim(str_replace($this->directory, '', $filename), DIRECTORY_SEPARATOR);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        array_pop($parts);

        // 2-char spread and md5 hash produce 32 dirs deep
        $this->assertCount(16, $parts);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetFileModeAsNonIntThrows()
    {
        $cache = $this->_getCacheDriver();

        // String permissions are not allowed
        $cache->setFileMode('ugo+rwx');
    }

    public function testSetFileMode()
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $this->markTestSkipped('The test ' . __METHOD__ .' requires a UNIX-like environment to test file permissions');
        }

        $cache = $this->_getCacheDriver();

        $test_mode = 0764;

        // Set file mode for testing
        $cache->setFileMode($test_mode);

        // Test save
        $cache->save('test_key', 'testing this out', 10);

        // access private methods
        $getFilename        = new \ReflectionMethod($cache, 'getFilename');
        $getNamespacedId    = new \ReflectionMethod($cache, 'getNamespacedId');

        $getFilename->setAccessible(true);
        $getNamespacedId->setAccessible(true);

        $id         = $getNamespacedId->invoke($cache, 'test_key');
        $filename   = $getFilename->invoke($cache, $id);

        $created_mode = fileperms($filename);

        $nice_test_mode = '0'.decoct($test_mode & 0777);
        $nice_created_mode = '0'.decoct($created_mode & 0777);

        $this->assertSame($nice_test_mode, $nice_created_mode);
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNull($stats[Cache::STATS_HITS]);
        $this->assertNull($stats[Cache::STATS_MISSES]);
        $this->assertNull($stats[Cache::STATS_UPTIME]);
        $this->assertEquals(0, $stats[Cache::STATS_MEMORY_USAGE]);
        $this->assertGreaterThan(0, $stats[Cache::STATS_MEMORY_AVAILABLE]);
    }

    protected function _getCacheDriver()
    {
        return new FilesystemCache($this->directory);
    }
}
