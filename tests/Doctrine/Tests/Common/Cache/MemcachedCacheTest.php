<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcachedCache;
use Memcached;
use function fsockopen;
use function sprintf;

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

    public function testGetMemcachedReturnsInstanceOfMemcached() : void
    {
        self::assertInstanceOf('Memcached', $this->_getCacheDriver()->getMemcached());
    }

    public function testContainsWithKeyWithFalseAsValue()
    {
        $testKey    = __METHOD__;
        $driver     = $this->_getCacheDriver();
        $reflection = new \ReflectionClass($driver);
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
