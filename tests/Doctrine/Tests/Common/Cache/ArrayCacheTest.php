<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;

class ArrayCacheTest extends CacheTest
{
    protected function getCacheDriver(): CacheProvider
    {
        return new ArrayCache();
    }

    public function testGetStats(): void
    {
        $cache = $this->getCacheDriver();
        $cache->fetch('test1');
        $cache->fetch('test2');
        $cache->fetch('test3');

        $cache->save('test1', 123);
        $cache->save('test2', 123);

        $cache->fetch('test1');
        $cache->fetch('test2');
        $cache->fetch('test3');

        $stats = $cache->getStats();
        self::assertEquals(2, $stats[Cache::STATS_HITS]);
        self::assertEquals(5, $stats[Cache::STATS_MISSES]); // +1 for internal call to DoctrineNamespaceCacheKey
        self::assertNotNull($stats[Cache::STATS_UPTIME]);
        self::assertNull($stats[Cache::STATS_MEMORY_USAGE]);
        self::assertNull($stats[Cache::STATS_MEMORY_AVAILABLE]);

        $cache->delete('test1');
        $cache->delete('test2');

        $cache->fetch('test1');
        $cache->fetch('test2');
        $cache->fetch('test3');

        $stats = $cache->getStats();
        self::assertEquals(2, $stats[Cache::STATS_HITS]);
        self::assertEquals(8, $stats[Cache::STATS_MISSES]); // +1 for internal call to DoctrineNamespaceCacheKey
    }

    protected function isSharedStorage(): bool
    {
        return false;
    }
}
