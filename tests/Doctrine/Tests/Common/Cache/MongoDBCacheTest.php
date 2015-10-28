<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\MongoDBCache;
use MongoClient;
use MongoCollection;

/**
 * @requires extension mongo
 */
class MongoDBCacheTest extends CacheTest
{
    /**
     * @var MongoCollection
     */
    private $collection;

    protected function setUp()
    {
        if ( ! version_compare(phpversion('mongo'), '1.3.0', '>=')) {
            $this->markTestSkipped('Mongo >= 1.3.0 is required.');
        }

        $mongo = new MongoClient();
        $this->collection = $mongo->selectCollection('doctrine_common_cache', 'test');
    }

    protected function tearDown()
    {
        if ($this->collection instanceof MongoCollection) {
            $this->collection->drop();
        }
    }

    public function testSaveWithNonUtf8String()
    {
        // Invalid 2-octet sequence
        $data = "\xc3\x28";

        $cache = $this->_getCacheDriver();

        $this->assertTrue($cache->save('key', $data));
        $this->assertEquals($data, $cache->fetch('key'));
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNull($stats[Cache::STATS_HITS]);
        $this->assertNull($stats[Cache::STATS_MISSES]);
        $this->assertGreaterThan(0, $stats[Cache::STATS_UPTIME]);
        $this->assertEquals(0, $stats[Cache::STATS_MEMORY_USAGE]);
        $this->assertNull($stats[Cache::STATS_MEMORY_AVAILABLE]);
    }

    protected function _getCacheDriver()
    {
        return new MongoDBCache($this->collection);
    }
}
