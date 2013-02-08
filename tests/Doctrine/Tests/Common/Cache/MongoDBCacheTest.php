<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\MongoDBCache;

class MongoDBCacheTest extends CacheTest
{
    private $_name;

    /**
     * @var \MongoCollection
     */
    private $_collection;


    public function setUp()
    {
        if (extension_loaded('mongo')) {
            $this->_name = str_replace('\\', '_', __CLASS__);
            $mongo = new \Mongo();
            $this->_collection = $mongo->selectCollection('db_' . $this->_name, $this->_name);
        } else {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of mongo');
        }
    }

    protected function _getCacheDriver()
    {
        $driver = new MongoDBCache();
        $driver->setCollection($this->_collection);
        return $driver;
    }

    public function tearDown()
    {
        $this->_collection->db->dropCollection(__CLASS__);
    }
}
