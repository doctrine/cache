<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use ArrayObject;

abstract class CacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    /**
     * @dataProvider provideCrudValues
     */
    public function testBasicCrudOperations($value)
    {
        $cache = $this->_getCacheDriver();

        // Test saving a value, checking if it exists, and fetching it back
        $this->assertTrue($cache->save('key', 'value'));
        $this->assertTrue($cache->contains('key'));
        $this->assertEquals('value', $cache->fetch('key'));

        // Test updating the value of a cache entry
        $this->assertTrue($cache->save('key', 'value-changed'));
        $this->assertTrue($cache->contains('key'));
        $this->assertEquals('value-changed', $cache->fetch('key'));

        // Test deleting a value
        $this->assertTrue($cache->delete('key'));
        $this->assertFalse($cache->contains('key'));
    }

    public function provideCrudValues()
    {
        return array(
            'array' => array(array('one', 2, 3.0)),
            'string' => array('value'),
            'integer' => array(1),
            'float' => array(1.5),
            'object' => array(new ArrayObject()),
        );
    }

    public function testDeleteAll()
    {
        $cache = $this->_getCacheDriver();

        $this->assertTrue($cache->save('key1', 1));
        $this->assertTrue($cache->save('key2', 2));
        $this->assertTrue($cache->deleteAll());
        $this->assertFalse($cache->contains('key1'));
        $this->assertFalse($cache->contains('key2'));
    }

    public function testFlushAll()
    {
        $cache = $this->_getCacheDriver();

        $this->assertTrue($cache->save('key1', 1));
        $this->assertTrue($cache->save('key2', 2));
        $this->assertTrue($cache->flushAll());
        $this->assertFalse($cache->contains('key1'));
        $this->assertFalse($cache->contains('key2'));
    }

    public function testNamespace()
    {
        $cache = $this->_getCacheDriver();

        $cache->setNamespace('ns1_');

        $this->assertTrue($cache->save('key1', 1));
        $this->assertTrue($cache->contains('key1'));

        $cache->setNamespace('ns2_');

        $this->assertFalse($cache->contains('key1'));
    }

    /**
     * @group DCOM-43
     */
    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertArrayHasKey(Cache::STATS_HITS, $stats);
        $this->assertArrayHasKey(Cache::STATS_MISSES, $stats);
        $this->assertArrayHasKey(Cache::STATS_UPTIME, $stats);
        $this->assertArrayHasKey(Cache::STATS_MEMORY_USAGE, $stats);
        $this->assertArrayHasKey(Cache::STATS_MEMORY_AVAILABLE, $stats);
    }

    public function testFetchMissShouldReturnFalse()
    {
        $cache = $this->_getCacheDriver();

        /* Ensure that caches return boolean false instead of null on a fetch
         * miss to be compatible with ORM integration.
         */
        $result = $cache->fetch('nonexistent_key');

        $this->assertFalse($result);
        $this->assertNotNull($result);
    }

    /**
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    abstract protected function _getCacheDriver();
}
