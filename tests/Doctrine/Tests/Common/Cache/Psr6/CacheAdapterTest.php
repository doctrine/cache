<?php

namespace Doctrine\Tests\Common\Cache\Psr6;

use Cache\IntegrationTests\CachePoolTest;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\DoctrineProvider as SymfonyDoctrineProvider;

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
}
