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
