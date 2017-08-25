<?php

namespace Doctrine\Tests\Common\Cache;

use Riak\Bucket;
use Riak\Connection;
use Riak\Exception;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\RiakCache;

/**
 * RiakCache test
 *
 * @group Riak
 * @requires extension riak
 */
class RiakCacheTest extends CacheTest
{
    /**
     * @var \Riak\Connection
     */
    private $connection;

    /**
     * @var \Riak\Bucket
     */
    private $bucket;

    protected function setUp() : void
    {
        try {
            $this->connection = new Connection('127.0.0.1', 8087);
            $this->bucket     = new Bucket($this->connection, 'test');
        } catch (Exception\RiakException $e) {
            $this->markTestSkipped('Cannot connect to Riak.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function testGetStats() : void
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        self::assertNull($stats);
    }

    /**
     * Retrieve RiakCache instance.
     */
    protected function _getCacheDriver() : CacheProvider
    {
        return new RiakCache($this->bucket);
    }
}
