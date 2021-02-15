<?php

namespace Doctrine\Common\Cache;

/**
 * Void cache driver. The cache could be of use in tests where you don`t need to cache anything.
 *
 * @deprecated Deprecated without replacement in doctrine/cache 1.11. This class will be dropped in 2.0
 *
 * @link   www.doctrine-project.org
 */
class VoidCache extends CacheProvider
{
    /**
     * {@inheritDoc}
     */
    protected function doFetch($id)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function doContains($id)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function doDelete($id)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function doFlush()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function doGetStats()
    {
        return;
    }
}
