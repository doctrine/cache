<?php

namespace Doctrine\Common\Cache;

/**
 * Zend Data Cache cache driver.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Ralph Schindler <ralph.schindler@zend.com>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ZendDataCache extends CacheProvider
{
    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        return zend_shm_cache_fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return (false !== zend_shm_cache_fetch($id));
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        return zend_shm_cache_store($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return zend_shm_cache_delete($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $namespace = $this->getNamespace();
        if (empty($namespace)) {
            return zend_shm_cache_clear();
        }
        return zend_shm_cache_clear($namespace);
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        return null;
    }
}
