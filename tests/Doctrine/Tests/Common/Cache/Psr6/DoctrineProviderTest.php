<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Tests\Common\Cache\Psr6;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\Tests\Common\Cache\ArrayCache;
use Doctrine\Tests\Common\Cache\CacheTest;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\DoctrineAdapter as SymfonyDoctrineAdapter;

use function sprintf;

class DoctrineProviderTest extends CacheTest
{
    protected function getCacheDriver(): CacheProvider
    {
        $pool = new ArrayAdapter();

        return DoctrineProvider::wrap($pool);
    }

    public function testProvider()
    {
        $cache = $this->getCacheDriver();

        $this->assertInstanceOf(CacheProvider::class, $cache);

        $key = '{}()/\@:';

        $this->assertTrue($cache->delete($key));
        $this->assertFalse($cache->contains($key));

        $this->assertTrue($cache->save($key, 'bar'));
        $this->assertTrue($cache->contains($key));
        $this->assertSame('bar', $cache->fetch($key));

        $this->assertTrue($cache->delete($key));
        $this->assertFalse($cache->fetch($key));
        $this->assertTrue($cache->save($key, 'bar'));

        $cache->flushAll();
        $this->assertFalse($cache->fetch($key));
        $this->assertFalse($cache->contains($key));
    }

    public function testWithWrappedCache()
    {
        $rootCache = new ArrayCache();
        $wrapped   = CacheAdapter::wrap($rootCache);

        self::assertSame($rootCache, DoctrineProvider::wrap($wrapped));
    }

    public function testWithWrappedSymfonyCache()
    {
        $rootCache = new ArrayCache();
        $wrapped   = new SymfonyDoctrineAdapter($rootCache);

        self::assertSame($rootCache, DoctrineProvider::wrap($wrapped));
    }

    public function testGetStats(): void
    {
        $this->markTestSkipped(sprintf('"%s" does not expose statistics', DoctrineProvider::class));
    }

    protected function isSharedStorage(): bool
    {
        return false;
    }
}
