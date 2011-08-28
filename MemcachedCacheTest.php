<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\MemcachedCache;

class MemcachedCacheTest extends CacheTest
{
    private $memcached;

    public function setUp()
    {
        if (extension_loaded('memcached')) {
            $this->memcached = new \Memcached();
            $this->memcached->setOption(\Memcached::OPT_COMPRESSION, false);
            $ok = $this->memcached->addServer('127.0.0.1', 11211);
            if (!$ok) {
                $this->markTestSkipped('The ' . __CLASS__ .' requires the use of memcache');
            }
        } else {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of memcache');
        }
    }

    protected function _getCacheDriver()
    {
        $driver = new MemcachedCache();
        $driver->setMemcached($this->memcached);
        return $driver;
    }
}