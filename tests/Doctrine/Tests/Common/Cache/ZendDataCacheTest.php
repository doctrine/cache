<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ZendDataCache;
use const PHP_SAPI;

/**
 * @requires function zend_shm_cache_fetch
 */
class ZendDataCacheTest extends CacheTest
{
    protected function setUp() : void
    {
        if (PHP_SAPI === 'apache2handler') {
            return;
        }

        $this->markTestSkipped('Zend Data Cache only works in apache2handler SAPI.');
    }

    public function testGetStats() : void
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        self::assertNull($stats);
    }

    protected function _getCacheDriver() : CacheProvider
    {
        return new ZendDataCache();
    }
}
