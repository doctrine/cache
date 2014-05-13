<?php
namespace Doctrine\Common\Cache;

use Elasticsearch\Client as ElasticSearch;

/**
 * ElasticSearch cache provider.
 *
 * @link   www.doctrine-project.org
 *
 * @author Eddie Jaoude <eddie@jaoudestudios.com>
 * @author Jeremy Quinton <jeremyquinton@gmail.com>
 */
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
     * @param ElasticSearch $elasticsearch
     */
    public function __construct(ElasticSearch $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * @param string $index
     *
     * @return ElasticSearchCache
     */
    public function setIndex($index)
    {
        $this->index = (string)$index;
    }

    /**
     * @param string $type
     *
     * @return ElasticSearchCache
     */
    public function setType($type)
    {
        $this->type = (string)$type;
    }

    /**
     * @return array
     */
    public function createCacheIndex()
    {
        $params = array(
            'index' => $this->index,
            'type'  => '',
            'body'  => array(
                'mappings' => array(
                    $this->type => array(
                        'properties' => array(
                            'data' =>
                                array(
                                    'type'  => 'string',
                                    'index' => 'not_analyzed'
                                )
                        )
                    )
                )
            )
        );

        return $this->elasticsearch->create($params);
    }

    /**
     * @param string $id
     *
     * @return array
     */
    private function getParams($id)
    {
        return array(
            'index' => strtolower($this->index),
            'type'  => strtolower($this->type),
            'id'    => (string)md5($id)
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function doFetch($id)
    {
        try {
            $response = $this->elasticsearch->get($this->getParams($id));
        } catch (\Exception $e) {
            return false;
        }
        if (!empty($response['_source']['data'])) {
            return unserialize($response['_source']['data']);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function doContains($id)
    {
        return $this->elasticsearch->exists($this->getParams($id));
    }

    /**
     * {@inheritDoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $response = $this->elasticsearch->index(
            array_merge(
                $this->getParams($id),
                array(
                    'body' => array(
                        'data' => serialize($data)
                    )
                )
            )
        );

        return empty($response['ok']) ? false : $response['ok'];
    }

    /**
     * {@inheritDoc}
     */
    protected function doDelete($id)
    {
        $response = $this->elasticsearch->delete($this->getParams($id));

        return empty($response['ok']) ? false : $response['ok'];
    }

    /**
     * {@inheritDoc}
     */
    protected function doFlush()
    {
        $response = $this->elasticsearch->indices()->delete(
            array(
                'index' => $this->index
            )
        );

        return empty($response['ok']) ? false : $response['ok'];
    }

    /**
     * {@inheritDoc}
     */
    protected function doGetStats()
    {
        return null;
    }
}
