<?php

declare(strict_types=1);

namespace Doctrine\Common\Cache;

use Couchbase\Bucket;
use Couchbase\Document;
use Couchbase\Exception;
use function phpversion;
use function serialize;
use function sprintf;
use function substr;
use function time;
use function unserialize;
use function version_compare;

/**
 * Couchbase ^2.3.0 cache provider.
 */
class CouchbaseBucketCache extends CacheProvider
{
    public const MINIMUM_VERSION = '2.3.0';

    public const KEY_NOT_FOUND = 13;

    public const MAX_KEY_LENGTH = 250;

    public const THIRTY_DAYS_IN_SECONDS = 2592000;

    /** @var Bucket */
    private $bucket;

    public function __construct()
    {
        if (version_compare(phpversion('couchbase'), self::MINIMUM_VERSION) < 0) {
            // Manager is required to flush cache and pull stats.
            throw new \RuntimeException(sprintf('ext-couchbase:^%s is required.', self::MINIMUM_VERSION));
        }
    }

    /**
     * Sets the Couchbase Bucket to use
     *
     * @param \Couchbase\Bucket $bucket
     */
    public function setBucket(Bucket $bucket): void
    {
        $this->bucket = $bucket;
    }

    /**
     * Gets the Couchbase bucket used by the cache
     *
     * @return \Couchbase\Bucket
     */
    public function getBucket(): Bucket
    {
        return $this->bucket;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $id = $this->normalizeKey($id);

        try {
            $document = $this->bucket->get($id);
        } catch (Exception $e) {
            return false;
        }

        if ($document instanceof Document && $document->value !== false) {
            return unserialize($document->value);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $id = $this->normalizeKey($id);

        try {
            $document = $this->bucket->get($id);
        } catch (Exception $e) {
            return false;
        }

        if ($document instanceof Document) {
            return ! $document->error;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $id = $this->normalizeKey($id);

        $lifeTime = $this->normalizeExpiry($lifeTime);

        try {
            $encoded = serialize($data);

            $document = $this->bucket->upsert($id, $encoded, [
                'expiry' => (int) $lifeTime,
            ]);
        } catch (Exception $e) {
            return false;
        }

        if ($document instanceof Document) {
            return ! $document->error;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $id = $this->normalizeKey($id);

        try {
            $document = $this->bucket->remove($id);
        } catch (Exception $e) {
            return $e->getCode() === self::KEY_NOT_FOUND;
        }

        if ($document instanceof Document) {
            return ! $document->error;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $manager = $this->bucket->manager();

        // Flush does not return with success or failure, and must be enabled per bucket on the server.
        // Store a marker item so that we will know if it was successful.
        $this->doSave(__METHOD__, true, 60);

        $manager->flush();

        if ($this->doContains(__METHOD__)) {
            $this->doDelete(__METHOD__);

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $manager          = $this->bucket->manager();
        $stats            = $manager->info();
        $nodes            = $stats['nodes'];
        $node             = $nodes[0];
        $interestingStats = $node['interestingStats'];

        return [
            Cache::STATS_HITS   => $interestingStats['get_hits'],
            Cache::STATS_MISSES => $interestingStats['cmd_get'] - $interestingStats['get_hits'],
            Cache::STATS_UPTIME => $node['uptime'],
            Cache::STATS_MEMORY_USAGE     => $interestingStats['mem_used'],
            Cache::STATS_MEMORY_AVAILABLE => $node['memoryFree'],
        ];
    }

    /**
     * Ensure key is less than 250 bytes in length
     * @see https://developer.couchbase.com/documentation/server/current/clustersetup/server-setup.html under "Limits"
     *
     * @param string $id
     * @return string
     */
    protected function normalizeKey(string $id) : string
    {
        $normalized = substr($id, 0, self::MAX_KEY_LENGTH);

        if ($normalized === false) {
            return $id;
        }

        return $normalized;
    }

    /**
     * Expiry treated as a unix timestamp instead of an offset if expiry is greater than 30 days.
     * @src https://developer.couchbase.com/documentation/server/4.1/developer-guide/expiry.html
     *
     * @param int $expiry
     * @return int
     */
    protected function normalizeExpiry(int $expiry) : int
    {
        if ($expiry > self::THIRTY_DAYS_IN_SECONDS) {
            return time() + $expiry;
        }

        return $expiry;
    }
}
