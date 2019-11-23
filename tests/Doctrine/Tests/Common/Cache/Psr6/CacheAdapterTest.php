<?php

namespace Doctrine\Tests\Common\Cache\Psr6;

use Cache\IntegrationTests\CachePoolTest;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Psr\Cache\CacheItemPoolInterface;

final class CacheAdapterTest extends CachePoolTest
{
    private $arrayCache;

    /**
     * @inheritDoc
     */
    public function createCachePool(): CacheItemPoolInterface
    {
        if (!$this->arrayCache) {
            $this->arrayCache = new ArrayCache();
        }

        return new CacheAdapter($this->arrayCache);
    }
}
