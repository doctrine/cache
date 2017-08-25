<?php

namespace Doctrine\Tests\Common\Cache;

use Couchbase;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\CouchbaseCache;

/**
 * @requires extension couchbase >=1.0
 * @requires extension couchbase <2.0
 */
class CouchbaseCacheTest extends CacheTest
{
    private $couchbase;

    protected function setUp() : void
    {
        try {
            $this->couchbase = new Couchbase('127.0.0.1', 'Administrator', 'password', 'default');
        } catch (\Exception $ex) {
             $this->markTestSkipped('Could not instantiate the Couchbase cache because of: ' . $ex);
        }
    }

    protected function _getCacheDriver() : CacheProvider
    {
        $driver = new CouchbaseCache();
        $driver->setCouchbase($this->couchbase);
        return $driver;
    }
}
