<?php

namespace Doctrine\Tests\Common\Cache;

use Aws\Common\Enum\Region;
use Aws\DynamoDb\DynamoDBClient;
use Doctrine\Common\Cache\DynamoDBCache;

class DynamoDBCacheTest extends CacheTest
{
    private $cacheCreated = false;

    public function setUp()
    {
        if (@fsockopen('127.0.0.1', 8000, $error_no, $error_str, 3) === false) {
            $this->markTestSkipped('The ' . __CLASS__ . ' cannot connect to dynamodb local');
        } else {
            $driver = $this->_getCacheDriver();
            $driver->createTable(1, 1);
            $this->cacheCreated = true;
        }
    }

    public function tearDown()
    {
        if ($this->cacheCreated) {
            $driver = $this->_getCacheDriver();
            $driver->deleteTable();
        }
    }

    protected function _getCacheDriver()
    {
        $dynamodb = DynamoDbClient::factory(
            array(
                'profile'  => 'default',
                'region'   => Region::US_EAST_1,
                'base_url' => 'http://localhost:8000', // DynamoDB Local
            )
        );
        $table    = 'doctrine_cache_test_' . $_SERVER['REQUEST_TIME'];
        $driver   = new DynamoDBCache($dynamodb, $table);
        return $driver;
    }
}
