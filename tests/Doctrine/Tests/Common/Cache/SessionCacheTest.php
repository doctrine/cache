<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\SessionCache;

class SessionCacheTest extends CacheTest
{
    protected function setUp()
    {
        @session_start();
        parent::setUp();
    }

    protected function _getCacheDriver()
    {
        return new SessionCache(false);
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNull($stats);
    }

    protected function isSharedStorage()
    {
        return false;
    }
}
