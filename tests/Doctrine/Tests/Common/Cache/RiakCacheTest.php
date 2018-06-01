<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\RiakCache;
use Riak\Bucket;
use Riak\Connection;
use Riak\Exception;
use function unserialize;

/**
 * RiakCache test
 *
 * @group Riak
 * @requires extension riak
 */
class RiakCacheTest extends CacheTest
{
    /** @var Connection */
    private $connection;

    /** @var Bucket */
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
     * @link https://github.com/doctrine/cache/pull/215
     */
    public function testResolveConflict()
    {
        $cache = $this->_getCacheDriver();

        $this->assertTrue($cache->save('1', 'value-1'));
        $this->assertTrue($cache->save('2', 'value-2'));

        $getNamespacedId = new \ReflectionMethod(RiakCache::class, 'getNamespacedId');
        $getNamespacedId->setAccessible(true);

        // faking the object list instead of modifying bucket properties to allow multi
        $response   = $this->bucket->get($getNamespacedId->invoke($cache, '1'));
        $vclock     = $response->getVClock();
        $objectList = [
            $response->getFirstObject(),
            $this->bucket->get($getNamespacedId->invoke($cache, '2'))->getFirstObject(),
        ];

        $resolveConflict = new \ReflectionMethod(RiakCache::class, 'resolveConflict');
        $resolveConflict->setAccessible(true);

        $object = $resolveConflict->invoke($cache, '1', $vclock, $objectList);

        $this->assertTrue($object !== null);
        $this->assertEquals('value-2', unserialize($object->getContent()));
    }

    /**
     * Retrieve RiakCache instance.
     */
    protected function _getCacheDriver() : CacheProvider
    {
        return new RiakCache($this->bucket);
    }
}
