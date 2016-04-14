<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ChainCache;

class ChainCacheTest extends CacheTest
{
    protected function _getCacheDriver()
    {
        return new ChainCache([new ArrayCache()]);
    }

    public function testLifetime()
    {
        $this->markTestSkipped('The ChainCache test uses ArrayCache which does not implement TTL currently.');
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertInternalType('array', $stats);
    }

    public function testOnlyFetchFirstOne()
    {
        $cache1 = new ArrayCache();
        $cache2 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $cache2->expects($this->never())->method('doFetch');

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->save('id', 'bar');

        $this->assertEquals('bar', $chainCache->fetch('id'));
    }

    public function testOnlyFetchFirstCompleteSet()
    {
        $cache1 = new ArrayCache();
        $cache2 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $cache2->expects($this->never())->method('doFetchMultiple');

        $chainCache = new ChainCache(array($cache1, $cache2));
        $chainCache->saveMultiple(array('bar' => 'Bar', 'foo' => 'Foo'));

        $this->assertEquals(array('bar' => 'Bar', 'foo' => 'Foo'), $chainCache->fetchMultiple(array('bar', 'foo')));
    }

    public function testFetchPropagateToFastestCache()
    {
        $cache1 = new ArrayCache();
        $cache2 = new ArrayCache();

        $cache2->save('bar', 'value');

        $chainCache = new ChainCache([$cache1, $cache2]);

        $this->assertFalse($cache1->contains('bar'));

        $result = $chainCache->fetch('bar');

        $this->assertEquals('value', $result);
        $this->assertTrue($cache2->contains('bar'));
    }

    public function testFetchMultiplePropagateToFastestCache()
    {
        $cache1 = new ArrayCache();
        $cache2 = new ArrayCache();

        $cache1->save('bar', 'Bar');
        $cache2->saveMultiple(array('bar' => 'Bar', 'foo' => 'Foo'));

        $chainCache = new ChainCache(array($cache1, $cache2));

        $this->assertTrue($cache1->contains('bar'));
        $this->assertFalse($cache1->contains('foo'));

        $result = $chainCache->fetchMultiple(array('bar', 'foo'));

        $this->assertEquals(array('bar' => 'Bar', 'foo' => 'Foo'), $result);
        $this->assertTrue($cache1->contains('foo'));
    }

    public function testNamespaceIsPropagatedToAllProviders()
    {
        $cache1 = new ArrayCache();
        $cache2 = new ArrayCache();

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->setNamespace('bar');

        $this->assertEquals('bar', $cache1->getNamespace());
        $this->assertEquals('bar', $cache2->getNamespace());
    }

    public function testDeleteToAllProviders()
    {
        $cache1 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');
        $cache2 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $cache1->expects($this->once())->method('doDelete');
        $cache2->expects($this->once())->method('doDelete');

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->delete('bar');
    }

    public function testDeleteMultipleToAllProviders()
    {
        $cache1 = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(array('doDeleteMultiple'))
            ->getMockForAbstractClass();
        $cache2 = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(array('doDeleteMultiple'))
            ->getMockForAbstractClass();

        $cache1->expects($this->once())->method('doDeleteMultiple')->willReturn(true);
        $cache2->expects($this->once())->method('doDeleteMultiple')->willReturn(true);

        $chainCache = new ChainCache(array($cache1, $cache2));
        $chainCache->deleteMultiple(array('bar', 'foo'));
    }

    public function testFlushToAllProviders()
    {
        $cache1 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');
        $cache2 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $cache1->expects($this->once())->method('doFlush');
        $cache2->expects($this->once())->method('doFlush');

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->flushAll();
    }

    protected function isSharedStorage()
    {
        return false;
    }
}
