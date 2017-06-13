<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\RedisCache;
use Redis;

/**
 * @requires extension redis
 */
class RedisCacheTest extends CacheTest
{
    private $_redis;

    protected function setUp() : void
    {
        $this->_redis = new Redis();
        $ok = @$this->_redis->connect('127.0.0.1');
        if (!$ok) {
            $this->markTestSkipped('Cannot connect to Redis.');
        }
    }

    public function testHitMissesStatsAreProvided() : void
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNotNull($stats[Cache::STATS_HITS]);
        $this->assertNotNull($stats[Cache::STATS_MISSES]);
    }

    public function testGetRedisReturnsInstanceOfRedis() : void
    {
        $this->assertInstanceOf(Redis::class, $this->_getCacheDriver()->getRedis());
    }

    public function testSerializerOptionWithOutIgbinaryExtension() : void
    {
        if (defined('Redis::SERIALIZER_IGBINARY') && extension_loaded('igbinary')) {
            $this->markTestSkipped('Extension igbinary is loaded.');
        }

        $this->assertEquals(
            Redis::SERIALIZER_PHP,
            $this->_getCacheDriver()->getRedis()->getOption(Redis::OPT_SERIALIZER)
        );
    }

    public function testSettingExplicitlySerializerOption() : void
    {
        $driver = $this->_getCacheDriver(Redis::SERIALIZER_NONE);

        $this->assertEquals(
            Redis::SERIALIZER_NONE,
            $driver->getRedis()->getOption(Redis::OPT_SERIALIZER)
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCacheDriver($serializer = null) : CacheProvider
    {
        $driver = new RedisCache();
        $driver->setRedis($this->_redis, $serializer);
        return $driver;
    }
}
