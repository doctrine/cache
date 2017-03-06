<?php

namespace Doctrine\Tests\Common\Cache;

use Basho\Riak;
use Basho\Riak\Command;
use Basho\Riak\Exception as RiakException;
use Basho\Riak\Node;
use Doctrine\Common\Cache\BashoRiakCache;

/**
 * BashoRiakCache test
 *
 * @group Riak
 * @requires basho/riak
 */
class BashoRiakCacheTest extends CacheTest
{
    /**
     * @var \Basho\Riak
     */
    private $riak;

    /**
     * @var \Basho\Riak\Command
     */
    private $command;

    protected function setUp()
    {
        try {
            $node = (new Node\Builder)
                ->atHost('127.0.0.1')
                ->onPort(8098)
                ->build();

            $this->riak = new Riak([$node]);
            $this->namespace = 'test';
        } catch (RiakException $e) {
            $this->markTestSkipped('Cannot connect to Riak.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertTrue(is_array($stats) && count($stats) > 0);
    }

    /**
     * Retrieve BashoRiakCache instance.
     *
     * @return \Doctrine\Common\Cache\BashoRiakCache
     */
    protected function _getCacheDriver()
    {
        return new BashoRiakCache($this->riak, $this->namespace);
    }
}
