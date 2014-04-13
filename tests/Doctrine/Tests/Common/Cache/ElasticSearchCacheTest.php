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
        if (class_exists('\Elasticsearch\Client')) {
            try {
                $this->elasticsearch = new ElasticSearch();
            } catch(Exception $ex) {
                $this->markTestSkipped('Could not instantiate the ElasticSearch cache because of: ' . $ex);
            }
        } else {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of the Elasticsearch dependency');
        }
    }

    public function tearDown()
    {
        if ($this->_getCacheDriver() instanceof ElasticSearchCache) {
            $this->_getCacheDriver()->deleteAll();
        }
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
        //have to call this method so that the index is created with
        $elasticsearchCacheDriver->createCacheIndex();

        return $elasticsearchCacheDriver;
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
    //todo I thinkg this can be removed?
    public function testSimpleSaveFetch()
    {
        $driver = $this->_getCacheDriver();
        $data = 'blah';
        $driver->save('test', $data);
        $this->assertEquals($data, $driver->fetch('test'));
    }


    public function testGetStats()
    {
        return null;
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
