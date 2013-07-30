<?php

namespace Doctrine\Tests\Common\Cache;

use Riak\Bucket;
use Riak\Connection;
use Doctrine\Common\Cache\RiakCache;

/**
 * RiakCache test
 *
 * @group Riak
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

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        if (extension_loaded('riak')) {
            try {
                $this->connection = new Connection('127.0.0.1', 8087);
                $this->bucket     = new Bucket($this->connection, 'test');
            } catch (\RiakException $e) {
                fwrite(STDOUT, $e->getMessage());

                $this->markTestSkipped('The ' . __CLASS__ .' requires the use of riak');
            }
        } else {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of riak');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNull($stats);
    }

    /**
     * Retrieve RiakCache instance.
     *
     * @return \Doctrine\Common\Cache\RiakCache
     */
    protected function _getCacheDriver()
    {
        $driver = new RiakCache($this->bucket);

        return $driver;
    }
}
