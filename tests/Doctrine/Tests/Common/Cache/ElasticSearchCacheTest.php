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
        if (!class_exists('\Elasticsearch\Client')) {
            $this->markTestSkipped('Could not instantiate the ElasticSearch cache because of: ' . $ex);
        }

        $this->elasticsearch = new ElasticSearch();
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
        $elasticsearchCacheDriver = new ElasticSearchCache($this->elasticsearch);
        $elasticsearchCacheDriver->setIndex('doctrinetest');
        $elasticsearchCacheDriver->setType('unittest');
        //have to call this method so that the index is created with
        $elasticsearchCacheDriver->createCacheIndex();

        return $elasticsearchCacheDriver;
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
