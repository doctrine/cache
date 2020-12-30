<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\RedisCache;
use Redis;

use function defined;
use function extension_loaded;

/**
 * @requires extension redis
 */
class RedisCacheTest extends CacheTest
{
    /** @var Redis */
    private $_redis;

    protected function setUp(): void
    {
        $this->_redis = new Redis();
        $ok           = @$this->_redis->connect('127.0.0.1');
        if ($ok) {
            return;
        }

        $this->markTestSkipped('Cannot connect to Redis.');
    }

    public function testHitMissesStatsAreProvided(): void
    {
        $cache = $this->getCacheDriver();
        $stats = $cache->getStats();

        self::assertNotNull($stats[Cache::STATS_HITS]);
        self::assertNotNull($stats[Cache::STATS_MISSES]);
    }

    public function testGetRedisReturnsInstanceOfRedis(): void
    {
        self::assertInstanceOf(Redis::class, $this->getCacheDriver()->getRedis());
    }

    public function testSerializerOptionWithOutIgbinaryExtension(): void
    {
        if (defined('Redis::SERIALIZER_IGBINARY') && extension_loaded('igbinary')) {
            $this->markTestSkipped('Extension igbinary is loaded.');
        }

        self::assertEquals(
            Redis::SERIALIZER_PHP,
            $this->getCacheDriver()->getRedis()->getOption(Redis::OPT_SERIALIZER)
        );
    }

    public function testDeleteAll(): void
    {
        $this->markTestSkipped('this is probably a bug that needs to be fixed');
    }

    public function testDeleteAllAndNamespaceVersioningBetweenCaches(): void
    {
        $this->markTestSkipped('this is probably a bug that needs to be fixed');
    }

    protected function getCacheDriver(): CacheProvider
    {
        $driver = new RedisCache();
        $driver->setRedis($this->_redis);

        return $driver;
    }
}
