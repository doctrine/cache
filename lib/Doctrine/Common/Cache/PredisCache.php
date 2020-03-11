<?php

namespace Doctrine\Common\Cache;

use Predis\ClientInterface;
use RuntimeException;
use function array_combine;
use function array_filter;
use function array_map;
use function call_user_func_array;
use function class_exists;
use function serialize;
use function unserialize;

/**
 * Predis cache provider.
 */
class PredisCache extends CacheProvider
{
    /** @var ClientInterface */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $result = $this->client->get($id);
        if ($result === null) {
            return false;
        }

        return unserialize($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchAtomic(string $id, callable $generator, int $ttl)
    {
        if (! class_exists('\Predis\Pipeline\Atomic', false)) {
            throw new RuntimeException('Atomic fetch (atomic pipeline) is not supported by this version of Predis');
        }

        return $this->client->pipeline(['atomic'], static function ($pipe) use ($id, $generator, $ttl) {
            $pipelineCache = new static($pipe);
            if ($pipelineCache->contains($id)) {
                return $pipelineCache->fetch($id);
            }

            $data = $generator($id);
            $pipelineCache->save($id, $data, $ttl);

            return $data;
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        $fetchedItems = call_user_func_array([$this->client, 'mget'], $keys);

        return array_map('unserialize', array_filter(array_combine($keys, $fetchedItems)));
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
                $response = (string) $this->client->setex($key, $lifetime, serialize($value));

                if ($response == 'OK') {
                    continue;
                }

                $success = false;
            }

            return $success;
        }

        // No lifetime, use MSET
        $response = $this->client->mset(array_map(static function ($value) {
            return serialize($value);
        }, $keysAndValues));

        return (string) $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return (bool) $this->client->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $data = serialize($data);
        if ($lifeTime > 0) {
            $response = $this->client->setex($id, $lifeTime, $data);
        } else {
            $response = $this->client->set($id, $data);
        }

        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->client->del($id) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteMultiple(array $keys)
    {
        return $this->client->del($keys) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $response = $this->client->flushdb();

        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = $this->client->info();

        return [
            Cache::STATS_HITS              => $info['Stats']['keyspace_hits'],
            Cache::STATS_MISSES            => $info['Stats']['keyspace_misses'],
            Cache::STATS_UPTIME            => $info['Server']['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE      => $info['Memory']['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE  => false,
        ];
    }
}
