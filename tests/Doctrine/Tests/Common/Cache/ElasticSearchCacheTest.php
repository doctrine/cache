<?php
namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ElasticSearchCache;
use Elasticsearch\Client as ElasticSearch;

/**
 * Class ElasticSearchCacheTest
 *
 * @package Doctrine\Tests\Common\Cache
 */
class ElasticSearchCacheTest extends CacheTest
{

    /**
     * @var ElasticSearch
     */
    private $elasticsearch;

    public function setUp()
    {
        $this->elasticsearch = new ElasticSearch();
    }

    public function tearDown()
    {
        if ($this->_getCacheDriver() instanceof ElasticSearchCache) {
            $this->_getCacheDriver()->deleteAll();
        }
    }

    public function testSetGetIndex()
    {
        $cacheDriver = $this->_getCacheDriver();

        $index = 'dummy-index';
        $cacheDriver->setIndex($index);

        $this->assertEquals($index, $cacheDriver->getIndex());
    }

    public function testSetGetType()
    {
        $cacheDriver = $this->_getCacheDriver();

        $type = 'dummy-type';
        $cacheDriver->setType($type);

        $this->assertEquals($type, $cacheDriver->getType());
    }

    public function testSimpleSaveFetch()
    {
        $driver = $this->_getCacheDriver();

        $data = 'blah';

        $driver->save('test', $data);

        $this->assertEquals($data, $driver->fetch('test'));
    }

    /**
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    protected function _getCacheDriver()
    {
        $elasticsearchCacheDriver = new ElasticSearchCache();
        $elasticsearchCacheDriver->setElasticSearch(
            $this->elasticsearch
        )
            ->setIndex('doctrinetest')
            ->setType('unittest');

        return $elasticsearchCacheDriver;
    }

    /**
     * Return whether multiple cache providers share the same storage.
     *
     * This is used for skipping certain tests for shared storage behavior.
     *
     * @return boolean
     */
    protected function isSharedStorage()
    {
        return false;
    }
}
