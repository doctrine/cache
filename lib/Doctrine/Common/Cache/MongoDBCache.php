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
 * MongoDB cache provider.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 */
class MongoDBCache extends CacheProvider
{

    /**
     * @var \MongoCollection
     */
    protected $collection;


    /**
     * Sets the collection to use.
     *
     * @param \MongoCollection $collection
     */
    public function setCollection(\MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Gets the mongo colletion used by the cache.
     *
     * @return \MongoCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }


    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $payload = $this->collection->findOne(array('cache_id' => $id), array('payload'));
        if (null === $payload) {
            return false;
        }

        return unserialize($payload['payload']);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return $this->collection->count(array('cache_id' => $id)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = false)
    {
        // unfortunately we have to serialize the data
        // on our own, otherwise objects aren't fetched correctly
        return $this->collection->save(array('cache_id' => $id, 'payload' => serialize($data)));
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->collection->remove(array('cache_id' => $id));
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->collection->remove(array());
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        return array(
            Cache::STATS_HITS => 0,
            Cache::STATS_MISSES => 0,
            Cache::STATS_UPTIME => 0,
            Cache::STATS_MEMORY_USAGE => 0,
            Cache::STATS_MEMORY_AVAILIABLE => 0
        );
    }
}