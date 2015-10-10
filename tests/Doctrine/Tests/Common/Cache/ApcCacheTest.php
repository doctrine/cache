<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ApcCache;

/**
 * @requires extension apc
 */
class ApcCacheTest extends CacheTest
{
    protected function _getCacheDriver()
    {
        return new ApcCache();
    }
}
