<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\MemcachedCache;
use Memcached;

/**
 * @requires extension memcached
 */
class MemcachedCacheTest extends CacheTest
{
    private $memcached;

    protected function setUp()
    {
        $this->memcached = new Memcached();
        $this->memcached->setOption(Memcached::OPT_COMPRESSION, false);
        $this->memcached->addServer('127.0.0.1', 11211);

        if (@fsockopen('127.0.0.1', 11211) === false) {
            unset($this->memcached);
            $this->markTestSkipped('Cannot connect to Memcached.');
        }
    }

    protected function tearDown()
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
    public function provideCacheIds()
    {
        $ids = parent::provideCacheIds();
        unset($ids[21], $ids[22], $ids[24]);

        return $ids;
    }

    public function testNoExpire()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('noexpire', 'value', 0);
        sleep(1);
        $this->assertTrue($cache->contains('noexpire'), 'Memcache provider should support no-expire');
    }

    public function testLongLifetime()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('key', 'value', 30 * 24 * 3600 + 1);
        $this->assertTrue($cache->contains('key'), 'Memcache provider should support TTL > 30 days');
    }

    public function testGetMemcachedReturnsInstanceOfMemcached()
    {
        $this->assertInstanceOf('Memcached', $this->_getCacheDriver()->getMemcached());
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCacheDriver()
    {
        $driver = new MemcachedCache();
        $driver->setMemcached($this->memcached);
        return $driver;
    }
}
