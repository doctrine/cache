<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\MongoDBCache;

class MongoDBCacheTest extends CacheTest
{
    private $name;

    /**
     * @var \MongoCollection
     */
    private $collection;


    public function setUp()
    {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of mongo');
            return;
        }

        $this->name = str_replace('\\', '_', __CLASS__);
        $mongo = new \Mongo();
        $this->collection = $mongo->selectCollection('db_' . $this->name, $this->name);
    }

    protected function _getCacheDriver()
    {
        $driver = new MongoDBCache();
        $driver->setCollection($this->collection);

        return $driver;
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNull($stats);
    }

    public function tearDown()
    {
        $this->collection->db->dropCollection($this->name);
    }
}
