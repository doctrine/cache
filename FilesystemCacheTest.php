<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\FilesystemCache;

/**
 * @group DCOM-101
 */
class FilesystemCacheTest extends CacheTest
{
    /**
     * @var \Doctrine\Common\Cache\FilesystemCache
     */
    private $driver;

    protected function _getCacheDriver()
    {
        $dir = sys_get_temp_dir() . "/doctrine_cache_". uniqid();
        $this->assertFalse(is_dir($dir));

        
        $this->driver = new FilesystemCache($dir);
        $this->assertTrue(is_dir($dir));

        return $this->driver;
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();
        $this->assertNull($stats);
    }


    public function tearDown()
    {
        $dir = $this->driver->getDirectory();
        $ext = $this->driver->getExtension();

        foreach (glob($dir . '/*' . $ext) as $file) {
            unlink($file);
        }

        rmdir($dir);
    }

}