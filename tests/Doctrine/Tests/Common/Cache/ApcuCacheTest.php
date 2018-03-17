<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\CacheProvider;
use function ini_get;

/**
 * @requires extension apcu
 */
class ApcuCacheTest extends CacheTest
{
    protected function setUp() : void
    {
        if (ini_get('apc.enable_cli')) {
            return;
        }

        $this->markTestSkipped('APC must be enabled for the CLI with the ini setting apc.enable_cli=1');
    }

    protected function _getCacheDriver() : CacheProvider
    {
        return new ApcuCache();
    }

    public function testLifetime() : void
    {
        $this->markTestSkipped('The APC cache TTL is not working in a single process/request. See https://bugs.php.net/bug.php?id=58084');
    }
}
