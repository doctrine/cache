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

use Basho\Riak;
use Basho\Riak\Command;
use Basho\Riak\Exception as RiakException;
use Basho\Riak\Object;

/**
 * Riak cache provider.
 *
 * @link   www.doctrine-project.org
 * @since  1.7
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class BashoRiakCache extends CacheProvider
{
    const EXPIRES_HEADER = 'X-Riak-Meta-Expires';

    /**
     * @var \Basho\Riak
     */
    private $riak;

    /**
     * @var string
     */
    private $bucketName;

    /**
     * Sets the riak bucket instance to use.
     *
     * @param \Basho\Riak $riak
     * @param string      $bucketName
     */
    public function __construct(Riak $riak, $bucketName)
    {
        $this->riak = $riak;
        $this->bucketName = $bucketName;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $id = urlencode($id);

        try {
            $command = (new Command\Builder\FetchObject($this->riak))
                ->buildLocation($id, $this->bucketName)
                ->build();

            $response = $command->execute();

            // No objects found
            if ($response->isNotFound()) {
                return false;
            }

            // Check for attempted siblings
            $object = ($response->hasSiblings())
                ? $this->resolveConflict(
                    $id,
                    $response->getObject()->getVclock(),
                    $response->getObject()->getMetaDataValue(self::EXPIRES_HEADER),
                    $response->getSiblings()
                )
                : $response->getObject();

            // Check for expired object
            if ($this->isExpired($object)) {
                try {
                    $command = (new Command\Builder\DeleteObject($this->riak))
                        ->buildLocation($id, $this->bucketName)
                        ->build();

                    $response = $command->execute();
                } catch (RiakException $e) {
                    // Do nothing
                }

                return false;
            }

            return unserialize($response->getObject()->getData());
        } catch (RiakException $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $id = urlencode($id);

        try {
            // We only need the HEAD, not the entire object
            // ... unfortunately, the API doesn't provide a HEAD method yet
            $command = (new Command\Builder\FetchObject($this->riak))
                ->buildLocation($id, $this->bucketName)
                ->build();

            $response = $command->execute();

            // No objects found
            if ($response->isNotFound()) {
                return false;
            }

            // Check for attempted siblings
            $object = $response->getObject();

            // Check for expired object
            if ($this->isExpired($object)) {
                try {
                    $command = (new Command\Builder\DeleteObject($this->riak))
                        ->buildLocation($id, $this->bucketName)
                        ->build();

                    $response = $command->execute();
                } catch (RiakException $e) {
                    // Do nothing
                }

                return false;
            }

            return true;
        } catch (RiakException $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $id = urlencode($id);

        try {
            $command = (new Command\Builder\StoreObject($this->riak))
                ->buildObject(serialize($data))
                ->buildLocation($id, $this->bucketName)
                ->build();

            if ($lifeTime > 0) {
                $object = $command->getObject();
                $object->setMetaDataValue(self::EXPIRES_HEADER, (string) (time() + $lifeTime));
            }

            $response = $command->execute();

            return true;
        } catch (RiakException $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $id = urlencode($id);

        try {
            $command = (new Command\Builder\DeleteObject($this->riak))
                ->buildLocation($id, $this->bucketName)
                ->build();

            $response = $command->execute();

            return true;
        } catch (RiakException $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        try {
            $command = (new Command\Builder\ListObjects($this->riak))
                ->buildBucket($this->bucketName)
                ->build();

            $response = $command->execute();

            if ($response->isNotFound()) {
                return false;
            }

            $data = $response->getObject()->getData();

            if ( ! count($data->keys)) {
                return false;
            }

            foreach ($data->keys as $id) {
                $id = urlencode($id);

                try {
                    $command = (new Command\Builder\DeleteObject($this->riak))
                        ->buildLocation($id, $this->bucketName)
                        ->build();

                    $response = $command->execute();
                } catch (RiakException $e) {
                    // Do nothing
                }
            }

            return true;
        } catch (RiakException $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        // Only exposed through HTTP stats API, not Protocol Buffers API
        try {
            $command = (new Command\Builder\FetchStats($this->riak))
                ->build();

            $response = $command->execute();

            return $response->getAllStats();
        } catch (RiakException $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * Check if a given Riak Object have expired.
     *
     * @param \Basho\Riak\Object $object
     *
     * @return bool
     */
    private function isExpired(Object $object)
    {
        $metadataMap = $object->getMetadata();

        return isset($metadataMap[self::EXPIRES_HEADER])
            && $metadataMap[self::EXPIRES_HEADER] < time();
    }

    /**
     * On-read conflict resolution. Applied approach here is last write wins.
     * Specific needs may override this method to apply alternate conflict resolutions.
     *
     * {@internal Riak does not attempt to resolve a write conflict, and store
     * it as sibling of conflicted one. By following this approach, it is up to
     * the next read to resolve the conflict. When this happens, your fetched
     * object will have a list of siblings (read as a list of objects).
     * In our specific case, we do not care about the intermediate ones since
     * they are all the same read from storage, and we do apply a last sibling
     * (last write) wins logic.
     * If by any means our resolution generates another conflict, it'll up to
     * next read to properly solve it.}
     *
     * @param string   $id
     * @param string   $vClock
     * @param string   $expires
     * @param Object[] $objectList
     *
     * @return \Basho\Riak\Object
     */
    protected function resolveConflict($id, $vClock, $expires, array $objectList)
    {
        // Our approach here is last-write wins
        $winner = $objectList[count($objectList) - 1];

        try {
            $command = (new Command\Builder\StoreObject($this->riak))
                ->buildObject(serialize($winner->getData()))
                ->buildLocation($id, $this->bucketName)
                ->build();

            $mergedObject = $command->getObject();
            $mergedObject->setVclock($vClock);

            if ($expires) {
                $mergedObject->setMetaDataValue(self::EXPIRES_HEADER, $expires);
            }

            $response = $command->execute();
        } catch (RiakException $e) {
            // Do nothing
        }

        return $mergedObject;
    }
}
