<?php

namespace Doctrine\Common\Cache;

use Memcached;

use function array_keys;
use function preg_match;
use function strlen;
use function strpos;
use function time;

/**
 * Memcached cache provider.
 *
 * @deprecated Deprecated without replacement in doctrine/cache 1.11. This class will be dropped in 2.0
 *
 * @link   www.doctrine-project.org
 */
class MemcachedCache extends CacheProvider
{
    public const CACHE_ID_MAX_LENGTH = 250;

    /** @var Memcached|null */
    private $memcached;

    /**
     * Sets the memcache instance to use.
     *
     * @return void
     */
    public function setMemcached(Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * Gets the memcached instance used by the cache.
     *
     * @return Memcached|null
     */
    public function getMemcached()
    {
        return $this->memcached;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        return $this->memcached->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        return $this->memcached->getMulti($keys) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {
        foreach (array_keys($keysAndValues) as $id) {
            $this->validateCacheId($id);
        }

        if ($lifetime > 30 * 24 * 3600) {
            $lifetime = time() + $lifetime;
        }

        return $this->memcached->setMulti($keysAndValues, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $this->memcached->get($id);

        return $this->memcached->getResultCode() === Memcached::RES_SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $this->validateCacheId($id);

        if ($lifeTime > 30 * 24 * 3600) {
            $lifeTime = time() + $lifeTime;
        }

        return $this->memcached->set($id, $data, (int) $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteMultiple(array $keys)
    {
        return $this->memcached->deleteMulti($keys)
            || $this->memcached->getResultCode() === Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->memcached->delete($id)
            || $this->memcached->getResultCode() === Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->memcached->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $stats   = $this->memcached->getStats();
        $servers = $this->memcached->getServerList();
        $key     = $servers[0]['host'] . ':' . $servers[0]['port'];
        $stats   = $stats[$key];

        return [
            Cache::STATS_HITS   => $stats['get_hits'],
            Cache::STATS_MISSES => $stats['get_misses'],
            Cache::STATS_UPTIME => $stats['uptime'],
            Cache::STATS_MEMORY_USAGE     => $stats['bytes'],
            Cache::STATS_MEMORY_AVAILABLE => $stats['limit_maxbytes'],
        ];
    }

    /**
     * Validate the cache id
     *
     * @see https://github.com/memcached/memcached/blob/1.5.12/doc/protocol.txt#L41-L49
     *
     * @param string $id
     *
     * @return void
     *
     * @throws InvalidCacheId
     */
    private function validateCacheId($id)
    {
        if (strlen($id) > self::CACHE_ID_MAX_LENGTH) {
            throw InvalidCacheId::exceedsMaxLength($id, self::CACHE_ID_MAX_LENGTH);
        }

        if (strpos($id, ' ') !== false) {
            throw InvalidCacheId::containsUnauthorizedCharacter($id, ' ');
        }

        if (preg_match('/[\t\r\n]/', $id) === 1) {
            throw InvalidCacheId::containsControlCharacter($id);
        }
    }
}
