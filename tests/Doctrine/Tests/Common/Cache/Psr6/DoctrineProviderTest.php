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
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use function class_exists;
use function sprintf;
use function sys_get_temp_dir;

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
        if (! class_exists(SymfonyDoctrineAdapter::class)) {
            self::markTestSkipped('This test requires Symfony 5 or lower.');
        }

        $rootCache = new ArrayCache();
        $wrapped   = new SymfonyDoctrineAdapter($rootCache);

        self::assertSame($rootCache, DoctrineProvider::wrap($wrapped));
    }

    public function testGetStats(): void
    {
        $this->markTestSkipped(sprintf('"%s" does not expose statistics', DoctrineProvider::class));
    }

    public function testResetArrayAdapter()
    {
        $cache = $this->getCacheDriver();

        $cache->save('test', 'test');

        $cache->reset();

        $this->assertSame(false, $cache->fetch('test'));
    }

    public function testResetFilesystemAdapter()
    {
        $pool   = new FilesystemAdapter('', 0, sys_get_temp_dir() . '/doctrine-cache-test');
        $pool2  = new FilesystemAdapter('', 0, sys_get_temp_dir() . '/doctrine-cache-test');
        $cache  = DoctrineProvider::wrap($pool);
        $cache2 = DoctrineProvider::wrap($pool2);

        $cache->save('test', 'test');
        $cache->reset();

        // we make sure with the next assertion the cache behave like expected and the test is not accidentally changed
        // to use ArrayAdapter as this test scenario requires a persisted cache adapter
        $this->assertSame('test', $cache->fetch('test'));

        // the second cache instance will now remove all exist files via namespaceVersion still the first cache
        // will receive the data until then the reset is called. the assertion after deleteAll is not required
        // but better show why the reset is even needed when cache service is used in long-running processes.
        $cache2->deleteAll();
        $this->assertSame('test', $cache->fetch('test'));
        $cache->reset();

        // the previous called reset will reset the namespaceVersion and so the cache is correctly false now
        $this->assertSame(false, $cache->fetch('test'));
    }

    protected function isSharedStorage(): bool
    {
        return false;
    }
}
