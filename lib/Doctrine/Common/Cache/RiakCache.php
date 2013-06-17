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

namespace Doctrine\Common\Cache;

/**
 * Riak cache provider.
 *
 * @link   www.doctrine-project.org
 * @since  2.4
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class RiakCache extends CacheProvider
{
    const EXPIRES_HEADER = 'X-Riak-Meta-Expires';

    /**
     * @var \RiakBucket|null
     */
    private $riakBucket;

    /**
     * Sets the riak bucket instance to use.
     *
     * @param \RiakBucket $riakBucket
     */
    public function __construct(\RiakBucket $riakBucket)
    {
        $this->riakBucket = $riakBucket;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        try {
            $object = $this->riakBucket->get(urlencode($id));

            if (isset($object->meta[self::EXPIRES_HEADER]) && $object->meta[self::EXPIRES_HEADER] < time()) {
                $this->riakBucket->delete($object);

                return false;
            }

            return unserialize($object->data);
        } catch (\RiakConflictedObjectException $e) {
            // TODO: We should be able to resolve Conflict resolution here.
            // May not be needed as of: https://github.com/TriKaspar/php_riak/issues/6
            // Exception provides two useful properties to help: vclock and objects
        } catch (\RiakException $e) {
            // Covers:
            // - RiakConnectionException
            // - RiakCommunicationException
            // - RiakResponseException
            // - RiakNotFoundException
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        try {
            $this->riakBucket->get(urlencode($id));

            return true;
        } catch (\RiakException $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        try {
            $object = new \RiakObject(urlencode($id));

            $object->data = serialize($data);

            if ($lifeTime > 0) {
                $object->metadata[self::EXPIRES_HEADER] = (string) (time() + $lifeTime);
            }

            $this->riakBucket->put($object);

            return true;
        } catch (\RiakException $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        try {
            $this->riakBucket->delete(urlencode($id));

            return true;
        } catch (\RiakBadArgumentsException $e) {
            // Key did not exist on cluster already
        } catch (\RiakException $e) {
            // Covers:
            // - RiakConnectionException
            // - RiakCommunicationException
            // - RiakResponseException
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        try {
            $keyList = $this->riakBucket->listKeys();

            foreach ($keyList as $key) {
                $this->riakBucket->delete($key);
            }

            return true;
        } catch (\RiakException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        // Only exposed through HTTP stats API, not Protocol Buffers API
        return null;
    }

    /**
     * Check if a given riak object have expired.
     *
     * @param RiakObject $object
     *
     * @return boolean
     */
    private function isExpired(RiakObject $object)
    {
        return (isset($object->metadata[self::EXPIRES_HEADER]) && $object->metadata[self::EXPIRES_HEADER] < time());
    }
}
