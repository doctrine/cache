<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\RiakCache;

class RiakCacheTest extends CacheTest
{
    /**
     * @var \RiakClient
     */
    private $riakClient;

    /**
     * @var \RiakBucket
     */
    private $riakBucket;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        if (extension_loaded('riak')) {
            try {
                $this->riakClient = new \RiakClient('127.0.0.1', 8087);
                $this->riakBucket = new \RiakBucket($this->riakClient, 'test');
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
        $driver = new RiakCache($this->riakBucket);

        return $driver;
    }
}
