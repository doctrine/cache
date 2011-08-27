<?php

namespace Doctrine\Tests\Common\Cache;

abstract class CacheTest extends \Doctrine\Tests\DoctrineTestCase
{
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

    public function testDeleteAll()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->deleteAll();

        $this->assertFalse($cache->contains('test_key1'));
        $this->assertFalse($cache->contains('test_key2'));
    }
    
    public function testFlushAll()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('test_key1', '1');
        $cache->save('test_key2', '2');
        $cache->flushAll();

        $this->assertFalse($cache->contains('test_key1'));
        $this->assertFalse($cache->contains('test_key2'));
    }

    public function testNamespace()
    {
        $cache = $this->_getCacheDriver();
        $cache->setNamespace('test_');
        $cache->save('key1', 'test');
        
        $this->assertTrue($cache->contains('key1'));
        
        $cache->setNamespace('test2_');
        
        $this->assertFalse($cache->contains('key1'));
    }

    abstract protected function _getCacheDriver();
}