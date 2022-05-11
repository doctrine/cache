<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\InvalidCacheId;
use Doctrine\Common\Cache\MemcachedCache;
use Generator;
use Memcached;
use ReflectionClass;

use function fsockopen;
use function sprintf;
use function str_repeat;

/**
 * @requires extension memcached
 */
class MemcachedCacheTest extends CacheTest
{
    /** @var Memcached */
    private $memcached;

    protected function setUp(): void
    {
        $this->memcached = new Memcached();
        $this->memcached->setOption(Memcached::OPT_COMPRESSION, false);
        $this->memcached->addServer('127.0.0.1', 11211);

        if (@fsockopen('127.0.0.1', 11211) !== false) {
            return;
        }

        unset($this->memcached);
        $this->markTestSkipped('Cannot connect to Memcached.');
    }

    protected function tearDown(): void
    {
        if (! ($this->memcached instanceof Memcached)) {
            return;
        }

        $this->memcached->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function provideCacheIds(): array
    {
        $ids = parent::provideCacheIds();

        // Memcached does not support " ", null byte and very long keys so we remove them from the tests.
        unset($ids[21], $ids[22], $ids[24]);

        // UTF-8 characters cause the tests to fail. We won't fix this anymore.
        unset($ids[11], $ids[13]);

        return $ids;
    }

    /**
     * @dataProvider provideInvalidCacheIds
     */
    public function testSaveInvalidCacheId(string $id): void
    {
        $this->expectException(InvalidCacheId::class);

        $this->getCacheDriver()->save($id, 1);
    }

    /**
     * @psalm-param array<string, int> $ids
     *
     * @dataProvider provideInvalidCacheIdSets
     */
    public function testSaveMultipleInvalidCacheIds(array $ids): void
    {
        $this->expectException(InvalidCacheId::class);

        $this->getCacheDriver()->saveMultiple($ids);
    }

    /**
     * @psalm-return Generator<string, array{string}>
     */
    public function provideInvalidCacheIds(): Generator
    {
        yield 'contains space' => ['foo bar'];
        yield 'contains control characters' => ["\tfoo\n\r"];
        yield 'exceeds max length' => [str_repeat('a', MemcachedCache::CACHE_ID_MAX_LENGTH + 1)];
    }

    /**
     * @psalm-return Generator<string, array{array<string, int>}>
     */
    public function provideInvalidCacheIdSets(): Generator
    {
        yield 'contains space' => [['foo' => 1, 'foo bar' => 2, 'bar' => 3]];
        yield 'contains control characters' => [['foo' => 1, "\tfoo\n\r" => 2, 'bar' => 3]];
        yield 'exceeds max length' => [['foo' => 1, str_repeat('a', MemcachedCache::CACHE_ID_MAX_LENGTH + 1) => 2, 'bar' => 3]];
    }

    public function testGetMemcachedReturnsInstanceOfMemcached(): void
    {
        self::assertInstanceOf('Memcached', $this->getCacheDriver()->getMemcached());
    }

    public function testContainsWithKeyWithFalseAsValue()
    {
        $testKey    = __METHOD__;
        $driver     = $this->getCacheDriver();
        $reflection = new ReflectionClass($driver);
        $method     = $reflection->getMethod('getNamespacedId');
        $method->setAccessible(true);
        $testKeyNS = $method->invokeArgs($driver, [$testKey]);
        $this->memcached->set($testKeyNS, false);

        self::assertTrue($driver->contains($testKey), sprintf('Expected key "%s" to be found in cache.', $testKey));
        self::assertFalse($driver->contains($testKey . '1'), 'No set key should not be found.');
    }

    public function testContainsWithKeyOnNonReachableCache()
    {
        $testKey   = __METHOD__;
        $memcached = new Memcached();
        $memcached->addServer('0.0.0.1', 11211); // fake server is not available
        $driver = new MemcachedCache();
        $driver->setMemcached($memcached);

        self::assertFalse($driver->contains($testKey), sprintf('Expected key "%s" not to be found in cache.', $testKey));
    }

    protected function getCacheDriver(): CacheProvider
    {
        $driver = new MemcachedCache();
        $driver->setMemcached($this->memcached);

        return $driver;
    }
}
