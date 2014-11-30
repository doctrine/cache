<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ChainCache;

class ChainCacheTest extends CacheTest
{
    protected function _getCacheDriver()
    {
        return new ChainCache(array(new ArrayCache()));
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNull($stats);
    }

    public function testOnlyFetchFirstOne()
    {
        $cache1 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');
        $cache2 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $cache1->expects($this->once())->method('doContains')->with('DoctrineNamespaceCacheKey[]')->will($this->returnValue(true));
        $cache1->expects($this->once())->method('doFetch')->with('DoctrineNamespaceCacheKey[]')->will($this->returnValue('bar'));

        $cache2->expects($this->never())->method('doContains');
        $cache2->expects($this->never())->method('doFetch');

        $chainCache = new ChainCache(array($cache1, $cache2));

        $this->assertEquals('bar', $chainCache->fetch('id'));
    }

    protected function isSharedStorage()
    {
        return false;
    }
}