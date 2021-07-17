<?php

namespace Doctrine\Tests\Common\Cache\Psr6;

use Cache\IntegrationTests\CachePoolTest;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\Tests\Common\Cache\ArrayCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\DoctrineProvider as SymfonyDoctrineProvider;

use function array_key_exists;
use function assert;

final class CacheAdapterTest extends CachePoolTest
{
    /** @var ArrayCache */
    private $arrayCache;

    public function createCachePool(): CacheItemPoolInterface
    {
        if (! $this->arrayCache) {
            $this->arrayCache = new ArrayCache();
        }

        return CacheAdapter::wrap($this->arrayCache);
    }

    public function testWithWrappedCache()
    {
        $rootCache = new ArrayAdapter();
        $wrapped   = DoctrineProvider::wrap($rootCache);

        self::assertSame($rootCache, CacheAdapter::wrap($wrapped));
    }

    /**
     * @requires function Symfony\Component\Cache\DoctrineProvider::__construct
     */
    public function testWithWrappedSymfonyCache()
    {
        $rootCache = new ArrayAdapter();
        $wrapped   = new SymfonyDoctrineProvider($rootCache);

        self::assertSame($rootCache, CacheAdapter::wrap($wrapped));
    }

    public function testWithWrappedMinimalCache()
    {
        $rootCache = new class implements Cache {
            /** @var mixed[] */
            public $values = [];

            /** @inheritdoc **/
            public function fetch($id)
            {
                return $values[$id] ?? false;
            }

            /** @inheritdoc **/
            public function contains($id)
            {
                return array_key_exists($id, $this->values);
            }

            /** @inheritdoc **/
            public function save($id, $data, $lifeTime = 0)
            {
                $this->values[$id] = $data;

                return true;
            }

            /** @inheritdoc **/
            public function delete($id)
            {
                unset($this->values[$id]);

                return true;
            }

            /** @inheritdoc **/
            public function getStats()
            {
                return null;
            }
        };

        $adapter = CacheAdapter::wrap($rootCache);
        self::assertInstanceOf(CacheAdapter::class, $adapter);
        assert($adapter instanceof CacheAdapter);

        /** @var CacheItemInterface[] $items */
        $items = $adapter->getItems(['1', '2', '3']);
        self::assertCount(3, $items);
        foreach ($items as $key => $item) {
            $item->set($key);
            $adapter->saveDeferred($item);
        }

        self::assertTrue($adapter->commit());
        self::assertCount(3, $rootCache->values);

        self::assertFalse($adapter->clear());
        self::assertCount(3, $rootCache->values);

        self::assertTrue($adapter->deleteItems(['1', '2']));
        self::assertCount(1, $rootCache->values);
    }

    public function testItemsAreFlushedToTheUnderlyingCacheOnce(): void
    {
        $wrapped = $this->createMock(Cache::class);

        $adapter   = CacheAdapter::wrap($wrapped);
        $cacheItem = $adapter->getItem('answer-to-life-universe-everything');
        $cacheItem->set(42);
        $adapter->saveDeferred($cacheItem);

        $wrapped->expects(self::once())
            ->method('save')
            ->willReturn(true);

        $adapter->commit();
        $adapter->commit();
    }

    public function testNamespacingFeatureIsPreservedWithDoctrineProvider(): void
    {
        $wrapped = new ArrayAdapter();

        $cacheApp1 = DoctrineProvider::wrap($wrapped);
        $cacheApp1->setNamespace('app 1');

        $cacheApp2 = DoctrineProvider::wrap($wrapped);
        $cacheApp2->setNamespace('app 2');

        $psrCacheApp1 = CacheAdapter::wrap($cacheApp1);
        $psrCacheApp2 = CacheAdapter::wrap($cacheApp2);

        $item = $psrCacheApp1->getItem('some key')->set('some value');
        $psrCacheApp1->save($item);
        self::assertFalse($psrCacheApp2->getItem('some key')->isHit());
    }

    /**
     * @requires function Symfony\Component\Cache\DoctrineProvider::__construct
     */
    public function testNamespacingFeatureIsPreservedWithSymfonyDoctrineProvider(): void
    {
        $wrapped = new ArrayAdapter();

        $cacheApp1 = new SymfonyDoctrineProvider($wrapped);
        $cacheApp1->setNamespace('app 1');

        $cacheApp2 = new SymfonyDoctrineProvider($wrapped);
        $cacheApp2->setNamespace('app 2');

        $psrCacheApp1 = CacheAdapter::wrap($cacheApp1);
        $psrCacheApp2 = CacheAdapter::wrap($cacheApp2);

        $item = $psrCacheApp1->getItem('some key')->set('some value');
        $psrCacheApp1->save($item);
        self::assertFalse($psrCacheApp2->getItem('some key')->isHit());
    }
}
