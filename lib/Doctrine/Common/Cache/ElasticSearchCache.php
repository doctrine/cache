<?php
namespace Doctrine\Common\Cache;

use Elasticsearch\Client as ElasticSearch;

class ElasticSearchCache extends CacheProvider
{

    /**
     * @var ElasticSearch|null
     */
    private $elasticsearch;

    /**
     * @var string
     */
    private $index = 'doctrine';

    /**
     * @var string
     */
    private $type = 'cache';

    /**
     * Sets the ElasticSearch instance to use.
     *
     * @param ElasticSearch $elasticsearch
     *
     * @return ElasticSearchCache
     */
    public function setElasticSearch(ElasticSearch $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;

        return $this;
    }

    /**
     * Gets the elasticsearch instance used by the cache.
     *
     * @return ElasticSearch|null
     */
    public function getElasticSearch()
    {
        return $this->elasticsearch;
    }

    /**
     * @param string $index
     *
     * @return ElasticSearchCache
     */
    public function setIndex($index)
    {
        $this->index = (string)$index;

        return $this;
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param string $type
     *
     * @return ElasticSearchCache
     */
    public function setType($type)
    {
        $this->type = (string)$type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    private function getParams($id)
    {
        return array(
            'index' => strtolower($this->getIndex()),
            'type'  => strtolower($this->getType()),
            'id'    => (string)$id
        );
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return string|bool The cached data or FALSE, if no cache entry exists for the given id.
     */
    protected function doFetch($id)
    {
        try {
            $response = $this->getElasticSearch()->get($this->getParams($id));
        } catch(\Exception $e) {
            return false;
        }

        if (!empty($response['_source']['data'])) {
            return $response['_source']['data'];
        }

        return false;
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    protected function doContains($id)
    {
        return $this->getElasticSearch()->exists($this->getParams($id));
    }

    /**
     * Puts data into the cache.
     *
     * @param string $id         The cache id.
     * @param string $data       The cache entry/data.
     * @param int    $lifeTime   The lifetime. If != 0, sets a specific lifetime for this
     *                           cache entry (0 => infinite lifeTime).
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $response = $this->getElasticSearch()->index(
            array_merge(
                $this->getParams($id),
                array(
                    'body' => array(
                        'data' => $data
                    )
                )
            )
        );

        empty($response['ok']) ? false : $response['ok'];
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function doDelete($id)
    {
        return $this->getElasticSearch()->delete($this->getParams($id));
    }

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function doFlush()
    {
        return $this->getElasticSearch()->delete(
            array(
                'index' => $this->getIndex()
            )
        );
    }

    /**
     * Retrieves cached information from the data store.
     *
     * @since 2.2
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    protected function doGetStats()
    {
        return null;
    }
}