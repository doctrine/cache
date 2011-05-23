<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ZendDataCache;

require_once __DIR__ . '/../../TestInit.php';

class ZendDataCacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function setUp()
    {
        if (!function_exists('zend_shm_cache_fetch') || (php_sapi_name() != 'apache2handler')) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of Zend Data Cache which only works in apache2handler SAPI');
        }
    }

    protected function _getCacheDriver()
    {
        return new ZendDataCache();
    }
    
    public function testBasics()
    {
        $cache = $this->_getCacheDriver();

        // Test save
        $cache->save('test_key', 'testing this out');

        // Test contains to test that save() worked
        $this->assertTrue($cache->contains('test_key'));

        // Test fetch
        $this->assertEquals('testing this out', $cache->fetch('test_key'));

        // Test delete
        $cache->save('test_key2', 'test2');
        $cache->delete('test_key2');
        $this->assertFalse($cache->contains('test_key2'));
    }
}