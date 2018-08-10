<?php

namespace Doctrine\Common\Cache;

use Redis;
use function array_combine;
use function defined;
use function extension_loaded;
use function is_bool;

/**
 * Redis cache provider.
 *
 * @link   www.doctrine-project.org
 */
class RedisCache extends CacheProvider
{
    public const SERIALIZER_PHP = 1;
    public const SERIALIZER_IGBINARY = 2;

    /** @var Redis|null */
    private $redis;

    /** @var int|null */
    private $serializer;

    /**
     * Sets the redis instance to use.
     *
     * @return void
     */
    public function setRedis(Redis $redis)
    {
        $this->redis = $redis;
        $this->serializer = $this->getSerializerValue();
    }

    /**
     * Gets the redis instance used by the cache.
     *
     * @return Redis|null
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $value = $this->redis->get($id);

        if (false === $value) {
            return false;
        }

        return $this->unserialize($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        $fetchedItems = array_combine($keys, $this->redis->mget($keys));

        // Redis mget returns false for keys that do not exist. So we need to filter those out unless it's the real data.
        $foundItems = [];

        foreach ($fetchedItems as $key => $value) {
            if ($value === false) {
                continue;
            }

            $foundItems[$key] = $this->unserialize($value);
        }

        return $foundItems;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {
        if ($lifetime) {
            $success = true;

            // Keys have lifetime, use SETEX for each of them
            foreach ($keysAndValues as $key => $value) {
                if ($this->redis->setex($key, $lifetime, $this->serialize($value))) {
                    continue;
                }

                $success = false;
            }

            return $success;
        }

        // No lifetime, use MSET
        return (bool) $this->redis->mset(
            array_map([$this, 'serialize'], $keysAndValues)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $exists = $this->redis->exists($id);

        if (is_bool($exists)) {
            return $exists;
        }

        return $exists > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            return $this->redis->setex($id, $lifeTime, $this->serialize($data));
        }

        return $this->redis->set($id, $this->serialize($data));
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->redis->delete($id) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteMultiple(array $keys)
    {
        return $this->redis->delete($keys) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->redis->flushDB();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = $this->redis->info();
        return [
            Cache::STATS_HITS   => $info['keyspace_hits'],
            Cache::STATS_MISSES => $info['keyspace_misses'],
            Cache::STATS_UPTIME => $info['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE      => $info['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE  => false,
        ];
    }

    protected function serialize($value)
    {
        if ($this->serializer === self::SERIALIZER_IGBINARY) {
            return igbinary_serialize($value);
        }

        return serialize($value);
    }

    protected function unserialize($value)
    {
        if ($this->serializer === self::SERIALIZER_IGBINARY) {
            return igbinary_unserialize($value);
        }

        return unserialize($value);
    }

    /**
     * Returns the serializer constant to use. If Redis is compiled with
     * igbinary support, that is used. Otherwise the default PHP serializer is
     * used.
     *
     * @return int One of the Redis::SERIALIZER_* constants
     */
    protected function getSerializerValue()
    {
        if (defined('Redis::SERIALIZER_IGBINARY') && extension_loaded('igbinary')) {
            return self::SERIALIZER_IGBINARY;
        }

        return self::SERIALIZER_PHP;
    }
}
