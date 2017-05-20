<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\XcacheCache;

/**
 * @requires extension xcache
 */
class XcacheCacheTest extends CacheTest
{
    protected function _getCacheDriver() : CacheProvider
    {
        return new XcacheCache();
    }
}
