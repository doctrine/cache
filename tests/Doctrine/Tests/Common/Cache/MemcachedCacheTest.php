<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcachedCache;
use Memcached;

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

        if (@fsockopen('127.0.0.1', 11211) === false) {
            unset($this->memcached);
            $this->markTestSkipped('Cannot connect to Memcached.');
        }
    }

    protected function tearDown() : void
    {
        if ($this->memcached instanceof Memcached) {
            $this->memcached->flush();
        }
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
        $this->assertInstanceOf('Memcached', $this->_getCacheDriver()->getMemcached());
    }

    public function testDoContains()
    {
        $driver = $this->_getCacheDriver();
        $reflection = new \ReflectionClass($driver);
        $method = $reflection->getMethod('doContains');
        $method->setAccessible(true);

        $testKey = __CLASS__.'#'.__METHOD__;

        $this->memcached->set($testKey, false);

        $this->assertTrue($method->invokeArgs($driver, [$testKey]));
        $this->assertFalse($method->invokeArgs($driver, [$testKey.'2']));

        $memcached = new Memcached();
        $memcached->addServer('0.0.0.1', 11211); // fake server is not available

        $driver = new MemcachedCache();
        $driver->setMemcached($memcached);
        $reflection = new \ReflectionClass($driver);
        $method = $reflection->getMethod('doContains');
        $method->setAccessible(true);

        $this->assertFalse($method->invokeArgs($driver, [$testKey]));
        $this->assertFalse($method->invokeArgs($driver, [$testKey.'2']));
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
