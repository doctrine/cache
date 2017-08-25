<?php

namespace Doctrine\Tests\Common\Cache;

use Couchbase\Bucket;
use Couchbase\Cluster;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\CouchbaseBucketCache;

/**
 * @requires extension couchbase >=2.3
 */
class CouchbaseBucketCacheTest extends CacheTest
{
    /** @var Bucket */
    private $bucket;

    protected function setUp() : void
    {
        $cluster      = new Cluster('couchbase://localhost?detailed_errcodes=1');
        $this->bucket = $cluster->openBucket('default');
    }

    protected function _getCacheDriver() : CacheProvider
    {
        return new CouchbaseBucketCache($this->bucket);
    }
}
