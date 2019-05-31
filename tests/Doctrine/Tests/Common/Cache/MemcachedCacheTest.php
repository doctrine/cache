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
    private $memcached;

    protected function setUp() : void
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

    protected function tearDown() : void
    {
        if (! ($this->memcached instanceof Memcached)) {
            return;
        }

        $this->memcached->flush();
    }

    /**
     * {@inheritdoc}
     *
     * Memcached does not support " ", null byte and very long keys so we remove them from the tests.
     */
    public function provideCacheIds() : array
    {
        $ids = parent::provideCacheIds();
        unset($ids[21], $ids[22], $ids[24]);

        return $ids;
    }

    /**
     * @dataProvider provideInvalidCacheIds
     */
    public function testSaveInvalidCacheId($id) : void
    {
        $this->expectException(InvalidCacheId::class);

        $this->_getCacheDriver()->save($id, 1);
    }

    /**
     * @dataProvider provideInvalidCacheIdSets
     */
    public function testSaveMultipleInvalidCacheIds(array $ids) : void
    {
        $this->expectException(InvalidCacheId::class);

        $this->_getCacheDriver()->saveMultiple($ids);
    }

    public function provideInvalidCacheIds() : Generator
    {
        yield 'contains space' => ['foo bar'];
        yield 'contains control characters' => ["\tfoo\n\r"];
        yield 'exceeds max length' => [str_repeat('a', MemcachedCache::CACHE_ID_MAX_LENGTH + 1)];
    }

    public function provideInvalidCacheIdSets() : Generator
    {
        yield 'contains space' => [['foo' => 1, 'foo bar' => 2, 'bar' => 3]];
        yield 'contains control characters' => [['foo' => 1, "\tfoo\n\r" => 2, 'bar' => 3]];
        yield 'exceeds max length' => [['foo' => 1, str_repeat('a', MemcachedCache::CACHE_ID_MAX_LENGTH + 1) => 2, 'bar' => 3]];
    }

    public function testGetMemcachedReturnsInstanceOfMemcached() : void
    {
        self::assertInstanceOf('Memcached', $this->_getCacheDriver()->getMemcached());
    }

    public function testContainsWithKeyWithFalseAsValue()
    {
        $testKey    = __METHOD__;
        $driver     = $this->_getCacheDriver();
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

    /**
     * {@inheritDoc}
     */
    protected function _getCacheDriver() : CacheProvider
    {
        $driver = new MemcachedCache();
        $driver->setMemcached($this->memcached);

        return $driver;
    }
}
