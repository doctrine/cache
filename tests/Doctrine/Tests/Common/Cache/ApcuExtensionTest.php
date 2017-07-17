<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\CacheProvider;

/**
 * Tests if APCu extension is installed
 */
class ApcuExtensionTest extends CacheTest
{
    protected function setUp() : void
    {
        if (function_exists('apcu_fetch')) {
            $this->markTestSkipped('APCu is actually enabled');
        }
    }

    protected function _getCacheDriver() : CacheProvider
    {
        return new ApcuCache();
    }

    public function testApcuExtension() : void
    {
        if (! function_exists('apcu_fetch')) {
            $this->expectException(RuntimeException::ApcuCache);

            new ApcuCache();
        } else {
            $this->markTestSkipped('APCu is actually enabled');
        }
    }
}