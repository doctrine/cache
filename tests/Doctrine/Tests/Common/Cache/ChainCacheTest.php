<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ChainCache;

class ChainCacheTest extends CacheTest
{
    protected function _getCacheDriver() : CacheProvider
    {
        return new ChainCache([new ArrayCache()]);
    }

    public function testGetStats() : void
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        self::assertInternalType('array', $stats);
    }

    public function testOnlyFetchFirstOne() : void
    {
        $cache1 = new ArrayCache();
        $cache2 = $this->getMockForAbstractClass(CacheProvider::class);

        $cache2->expects($this->never())->method('doFetch');

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->save('id', 'bar');

        self::assertEquals('bar', $chainCache->fetch('id'));
    }

    public function testOnlyFetchFirstCompleteSet() : void
    {
        $cache1 = new ArrayCache();
        $cache2 = $this
            ->getMockBuilder(CacheProvider::class)
            ->setMethods(['doFetchMultiple'])
            ->getMockForAbstractClass();

        $cache2->expects($this->never())->method('doFetchMultiple');

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->saveMultiple(['bar' => 'Bar', 'foo' => 'Foo']);

        self::assertEquals(['bar' => 'Bar', 'foo' => 'Foo'], $chainCache->fetchMultiple(['bar', 'foo']));
    }

    public function testFetchPropagateToFastestCache() : void
    {
        $cache1 = new ArrayCache();
        $cache2 = new ArrayCache();

        $cache2->save('bar', 'value');

        $chainCache = new ChainCache([$cache1, $cache2]);

        self::assertFalse($cache1->contains('bar'));

        $result = $chainCache->fetch('bar');

        self::assertEquals('value', $result);
        self::assertTrue($cache1->contains('bar'));
    }

    public function testFetchMultiplePropagateToFastestCache() : void
    {
        $cache1 = new ArrayCache();
        $cache2 = new ArrayCache();

        $cache1->save('bar', 'Bar');
        $cache2->saveMultiple(['bar' => 'Bar', 'foo' => 'Foo']);

        $chainCache = new ChainCache([$cache1, $cache2]);

        self::assertTrue($cache1->contains('bar'));
        self::assertFalse($cache1->contains('foo'));

        $result = $chainCache->fetchMultiple(['bar', 'foo']);

        self::assertEquals(['bar' => 'Bar', 'foo' => 'Foo'], $result);
        self::assertTrue($cache1->contains('foo'));
    }

    public function testNamespaceIsPropagatedToAllProviders() : void
    {
        $cache1 = new ArrayCache();
        $cache2 = new ArrayCache();

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->setNamespace('bar');

        self::assertEquals('bar', $cache1->getNamespace());
        self::assertEquals('bar', $cache2->getNamespace());
    }

    public function testDeleteToAllProviders() : void
    {
        $cache1 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');
        $cache2 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $cache1->expects($this->once())->method('doDelete');
        $cache2->expects($this->once())->method('doDelete');

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->delete('bar');
    }

    public function testDeleteMultipleToAllProviders() : void
    {
        $cache1 = $this
            ->getMockBuilder(CacheProvider::class)
            ->setMethods(['doDeleteMultiple'])
            ->getMockForAbstractClass();
        $cache2 = $this
            ->getMockBuilder(CacheProvider::class)
            ->setMethods(['doDeleteMultiple'])
            ->getMockForAbstractClass();

        $cache1->expects($this->once())->method('doDeleteMultiple')->willReturn(true);
        $cache2->expects($this->once())->method('doDeleteMultiple')->willReturn(true);

        $chainCache = new ChainCache(array($cache1, $cache2));
        $chainCache->deleteMultiple(array('bar', 'foo'));
    }

    public function testFlushToAllProviders() : void
    {
        $cache1 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');
        $cache2 = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $cache1->expects($this->once())->method('doFlush');
        $cache2->expects($this->once())->method('doFlush');

        $chainCache = new ChainCache([$cache1, $cache2]);
        $chainCache->flushAll();
    }

    /**
     * @group 155
     *
     * @return void
     */
    public function testChainCacheAcceptsArrayIteratorsAsDependency() : void
    {
        $cache1 = $this->getMockForAbstractClass(CacheProvider::class);
        $cache2 = $this->getMockForAbstractClass(CacheProvider::class);

        $cache1->expects($this->once())->method('doFlush');
        $cache2->expects($this->once())->method('doFlush');

        (new ChainCache(new \ArrayIterator([$cache1, $cache2])))->flushAll();
    }

    protected function isSharedStorage() : bool
    {
        return false;
    }
}
