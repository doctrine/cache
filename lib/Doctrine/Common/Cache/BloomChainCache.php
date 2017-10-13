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

use RocketLabs\BloomFilter\BloomFilterAbstract;


/**
 * Cache provider that uses bloom filter in front of other cache providers
 *
 * @author Igor Veremchuk <igor.veremchuk@gmail.com>
 */
class BloomChainCache extends ChainCache
{
    /** @var BloomFilterAbstract */
    protected $bloomFilter;

    /**
     * @param BloomFilterAbstract $bloomFilter
     * @param array $cacheProviders
     */
    public function __construct(BloomFilterAbstract $bloomFilter , $cacheProviders = [])
    {
       parent::__construct($cacheProviders);
       $this->bloomFilter = $bloomFilter;
    }

    /**
     * {@inheritDoc}
     */
    protected function doFetch($id)
    {
        if (!$this->bloomFilter->has($id)) {
            return false;
        }

        return parent::doFetch($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        $fetchedValues = parent::doFetchMultiple(
            array_filter(
                $keys,
                function($key) {
                    return $this->bloomFilter->has($key);
                }
            )
        );

        return array_merge(array_fill_keys($keys, false), $fetchedValues);
    }

    /**
     * {@inheritDoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $this->bloomFilter->add($id);

        return parent::doSave($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {
        $this->bloomFilter->addBulk(array_keys($keysAndValues));

        return parent::doSaveMultiple($keysAndValues, $lifetime);
    }

    /**
     * {@inheritDoc}
     */
    protected function doDelete($id)
    {
        throw new NotSupportedException(
            'Bloom Filter does not support deleting. In case of using delete functionality please use "Counting Bloom Filter"'
        );
        //return parent::doDelete($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteMultiple(array $keys)
    {
        throw new NotSupportedException(
            'Bloom Filter does not support deleting. In case of using delete functionality please use "Counting Bloom Filter"'
        );
        //return parent::doDeleteMultiple($keys);
    }

    /**
     * {@inheritDoc}
     */
    protected function doFlush()
    {
        $this->bloomFilter->reset();

        return parent::doFlush();
    }

}
