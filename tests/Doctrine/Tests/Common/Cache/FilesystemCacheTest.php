<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;

/**
 * @group DCOM-101
 */
class FilesystemCacheTest extends BaseFileCacheTest
{
    public function testGetStats() : void
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        self::assertNull($stats[Cache::STATS_HITS]);
        self::assertNull($stats[Cache::STATS_MISSES]);
        self::assertNull($stats[Cache::STATS_UPTIME]);
        self::assertEquals(0, $stats[Cache::STATS_MEMORY_USAGE]);
        self::assertGreaterThan(0, $stats[Cache::STATS_MEMORY_AVAILABLE]);
    }

    public function testCacheInSharedDirectoryIsPerExtension() : void
    {
        $cache1 = new FilesystemCache($this->directory, '.foo');
        $cache2 = new FilesystemCache($this->directory, '.bar');

        self::assertTrue($cache1->save('key1', 11));
        self::assertTrue($cache1->save('key2', 12));

        self::assertTrue($cache2->save('key1', 21));
        self::assertTrue($cache2->save('key2', 22));

        self::assertSame(11, $cache1->fetch('key1'), 'Cache value must not be influenced by a different cache in the same directory but different extension');
        self::assertSame(12, $cache1->fetch('key2'));
        self::assertTrue($cache1->flushAll());
        self::assertFalse($cache1->fetch('key1'), 'flushAll() must delete all items with the current extension');
        self::assertFalse($cache1->fetch('key2'));

        self::assertSame(21, $cache2->fetch('key1'), 'flushAll() must not remove items with a different extension in a shared directory');
        self::assertSame(22, $cache2->fetch('key2'));
    }

    public function testFlushAllWithNoExtension() : void
    {
        $cache = new FilesystemCache($this->directory, '');

        self::assertTrue($cache->save('key1', 1));
        self::assertTrue($cache->save('key2', 2));
        self::assertTrue($cache->flushAll());
        self::assertFalse($cache->contains('key1'));
        self::assertFalse($cache->contains('key2'));
    }

    protected function _getCacheDriver() : CacheProvider
    {
        return new FilesystemCache($this->directory);
    }
}
