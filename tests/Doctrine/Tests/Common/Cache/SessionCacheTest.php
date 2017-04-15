<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\SessionCache;

class SessionCacheTest extends CacheTest
{
    protected function _getCacheDriver()
    {
        return new SessionCache();
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNull($stats);
    }
}