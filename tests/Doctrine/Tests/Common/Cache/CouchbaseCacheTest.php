<?php

namespace Doctrine\Tests\Common\Cache;

use Couchbase;
use Doctrine\Common\Cache\CouchbaseCache;

/**
 * @requires extension couchbase
 */
class CouchbaseCacheTest extends CacheTest
{
    private $couchbase;

    protected function setUp()
    {
        try {
            $this->couchbase = new Couchbase('127.0.0.1', 'Administrator', 'password', 'default');
        } catch(Exception $ex) {
             $this->markTestSkipped('Could not instantiate the Couchbase cache because of: ' . $ex);
        }
    }

    public function testNoExpire()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('noexpire', 'value', 0);
        sleep(1);
        $this->assertTrue($cache->contains('noexpire'), 'Couchbase provider should support no-expire');
    }

    public function testLongLifetime()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('key', 'value', 30 * 24 * 3600 + 1);

        $this->assertTrue($cache->contains('key'), 'Couchbase provider should support TTL > 30 days');
    }

    protected function _getCacheDriver()
    {
        $driver = new CouchbaseCache();
        $driver->setCouchbase($this->couchbase);
        return $driver;
    }
}