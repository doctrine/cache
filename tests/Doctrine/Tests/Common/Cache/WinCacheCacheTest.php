<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\WinCacheCache;

/**
 * @requires extension wincache
 */
class WinCacheCacheTest extends CacheTest
{
    protected function _getCacheDriver() : CacheProvider
    {
        return new WinCacheCache();
    }
}
