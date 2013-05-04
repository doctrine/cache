<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;

abstract class CacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testBasics()
    {
        $cache = $this->_getCacheDriver();

        // Test save
        $cache->save('test_key', 'testing this out');

        // Test contains to test that save() worked
        $this->assertTrue($cache->contains('test_key'));

        // Test fetch
        $this->assertEquals('testing this out', $cache->fetch('test_key'));

        // Test delete
        $cache->save('test_key2', 'test2');
        $cache->delete('test_key2');
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testSaveWithCustomTimeArrayAccess()
    {
        $cache = $this->_getCacheDriver();
        $cache['test_key.1'] = 'test';
        $this->assertTrue(isset($cache['test_key']));
        $this->assertEquals('test', $cache['test_key']);

        $cache['test.key.dot_test.1'] = 'test2';
        $this->assertTrue(isset($cache['test.key.dot_test']));
        $this->assertEquals('test2', $cache['test.key.dot_test']);
    }

    public function testBasicsArrayAccess()
    {
        $cache = $this->_getCacheDriver();

        // Test save
        $cache['test_key'] = 'testing this out';
        $this->assertTrue(isset($cache['test_key']));

        // Test fetch
        $this->assertEquals('testing this out', $cache['test_key']);

        // Test delete
        $cache['test_key2'] = 'test2';
        unset($cache['test_key2']);
        $this->assertFalse(isset($cache['test_key2']));
    }

    public function testObjects()
    {
        $cache = $this->_getCacheDriver();

        // Fetch/save test with objects (Is cache driver serializes/unserializes objects correctly ?)
        $cache->save('test_object_key', new \ArrayObject());
        $this->assertTrue($cache->fetch('test_object_key') instanceof \ArrayObject);
    }

    public function testObjectsArrayAccess()
    {
        $cache = $this->_getCacheDriver();

        $cache['test_object_key'] = new \ArrayObject();
        $this->assertTrue($cache['test_object_key'] instanceof \ArrayObject);
    }

    public function testDeleteAll()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->deleteAll();

        $this->assertFalse($cache->contains('test_key1'));
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testDeleteAllArrayAccess()
    {
        $cache = $this->_getCacheDriver();
        $cache['test_key1'] = '1';
        $cache['test_key2'] = '2';
        unset($cache['all']);

        $this->assertFalse(($cache['test_key1']));
        $this->assertFalse(isset($cache['test_key2']));
    }

    public function testFlushAll()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->flushAll();

        $this->assertFalse($cache->contains('test_key1'));
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testNamespace()
    {
        $cache = $this->_getCacheDriver();
        $cache->setNamespace('test_');
        $cache->save('key1', 'test');

        $this->assertTrue($cache->contains('key1'));

        $cache->setNamespace('test2_');

        $this->assertFalse($cache->contains('key1'));
    }

    /**
     * @group DCOM-43
     */
    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertArrayHasKey(Cache::STATS_HITS,   $stats);
        $this->assertArrayHasKey(Cache::STATS_MISSES, $stats);
        $this->assertArrayHasKey(Cache::STATS_UPTIME, $stats);
        $this->assertArrayHasKey(Cache::STATS_MEMORY_USAGE, $stats);
        $this->assertArrayHasKey(Cache::STATS_MEMORY_AVAILABLE, $stats);
    }

    /**
     * Make sure that all supported caches return "false" instead of "null" to be compatible
     * with ORM integration.
     */
    public function testFalseOnFailedFetch()
    {
        $cache = $this->_getCacheDriver();
        $result = $cache->fetch('nonexistent_key');
        $this->assertFalse($result);
        $this->assertNotNull($result);
    }

    public function testFalseOnFailedFetchArrayAccess()
    {
        $cache = $this->_getCacheDriver();
        $result = $cache['nonexistent_key'];
        $this->assertFalse($result);
        $this->assertNotNull($result);
    }

    /**
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    abstract protected function _getCacheDriver();
}
