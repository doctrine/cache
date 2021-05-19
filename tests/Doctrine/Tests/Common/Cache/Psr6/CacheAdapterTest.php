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
}
