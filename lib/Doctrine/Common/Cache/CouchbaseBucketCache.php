<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

declare(strict_types=1);

namespace Doctrine\Common\Cache;

use Couchbase\Bucket;
use Couchbase\Document;
use Couchbase\Exception;

/**
 * Couchbase ^2.3.0 cache provider.
 */
final class CouchbaseBucketCache extends CacheProvider
{
    private const MINIMUM_VERSION = '2.3.0';

    private const KEY_NOT_FOUND = 13;

    private const MAX_KEY_LENGTH = 250;

    private const THIRTY_DAYS_IN_SECONDS = 2592000;

    /**
     * @var Bucket
     */
    private $bucket;

    /**
     * CouchbaseCache constructor.
     * @param Bucket $bucket
     */
    public function __construct(Bucket $bucket)
    {
        if (version_compare(phpversion('couchbase'), self::MINIMUM_VERSION) < 0) {
            // Manager is required to flush cache and pull stats.
            throw new \RuntimeException(sprintf('ext-couchbase:^%s is required.', self::MINIMUM_VERSION));
        }

        $this->bucket = $bucket;
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
     * @param string $id
     * @return string
     */
    private function normalizeKey(string $id) : string
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
    private function normalizeExpiry(int $expiry) : int
    {
        if ($expiry > self::THIRTY_DAYS_IN_SECONDS) {
            return time() + $expiry;
        }

        return $expiry;
    }
}
