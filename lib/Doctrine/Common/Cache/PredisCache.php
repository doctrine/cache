<?php

namespace Doctrine\Common\Cache;

use Predis\Client;

/**
 * Predis cache provider.
 *
 * @author othillo <othillo@othillo.nl>
 */
class PredisCache extends CacheProvider
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $result = $this->client->get($id);
        if (null === $result) {
            return false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return $this->client->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
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
        return $this->client->del($id) > 0;
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

        return array(
            Cache::STATS_HITS              => false,
            Cache::STATS_MISSES            => false,
            Cache::STATS_UPTIME            => $info['Server']['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE      => $info['Memory']['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE  => false
        );
    }
}
