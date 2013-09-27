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
 * Base class for cache provider implementations.
 *
 * @since  2.2
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
abstract class CacheProvider implements Cache
{
    const DOCTRINE_NAMESPACE_CACHEKEY = 'DoctrineNamespaceCacheKey[%s]';

    /**
     * @var \Doctrine\Common\Cache\CacheNamespace
     */
    private $cacheNamespace;

    /**
     * Sets the namespace to prefix all cache ids with.
     *
     * @param string $namespace
     *
     * @return void
     */
    public function setNamespace($namespace)
    {
        if ($this->cacheNamespace == null) {
            $this->cacheNamespace = new DefaultCacheNamespace($this, self::DOCTRINE_NAMESPACE_CACHEKEY);
        }

        $this->cacheNamespace->setNamespace($namespace);
    }

    /**
     * Retrieves the namespace that prefixes all cache ids.
     *
     * @return string
     */
    public function getNamespace()
    {
        if ($this->cacheNamespace == null) {
            return null;
        }

        return $this->cacheNamespace->getNamespace();
    }

    /**
     * Sets the CacheNamespace
     *
     * @param \Doctrine\Common\Cache\CacheNamespace $cacheNamespace The cache namespace or NULL to disable it.
     */
    public function setCacheNamespace(CacheNamespace $cacheNamespace = null)
    {
        $this->cacheNamespace = $cacheNamespace;
    }

    /**
     * @return \Doctrine\Common\Cache\CacheNamespace
     */
    public function getCacheNamespace()
    {
        return $this->cacheNamespace;
    }

    /**
     * Fetches an entry from the cache.
     * Does not apply any cache key change.
     *
     * @param string $key The key of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetchUsingRealKey($id)
    {
        return $this->doFetch($id);
    }

    /**
     * Puts data into the cache.
     * Does not apply any cache key change.
     *
     * @param string $id       The cache id.
     * @param mixed  $data     The cache entry/data.
     * @param int    $lifeTime The cache lifetime.
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function saveUsingRealKey($id, $data, $lifeTime = 0)
    {
        return $this->doSave($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        if ($this->cacheNamespace) {
            $id = $this->cacheNamespace->getNamespacedKey($id);
        }

        return $this->doFetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        if ($this->cacheNamespace) {
            $id = $this->cacheNamespace->getNamespacedKey($id);
        }

        return $this->doContains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if ($this->cacheNamespace) {
            $id = $this->cacheNamespace->getNamespacedKey($id);
        }

        return $this->doSave($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if ($this->cacheNamespace) {
            $id = $this->cacheNamespace->getNamespacedKey($id);
        }

        return $this->doDelete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return $this->doGetStats();
    }

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll()
    {
        return $this->doFlush();
    }

    /**
     * Deletes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully deleted, FALSE otherwise.
     */
    public function deleteAll()
    {
        if ($this->cacheNamespace === null) {
            $this->cacheNamespace = new DefaultCacheNamespace($this, self::DOCTRINE_NAMESPACE_CACHEKEY);
        }

        return $this->cacheNamespace->increment();
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return string|bool The cached data or FALSE, if no cache entry exists for the given id.
     */
    abstract protected function doFetch($id);

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    abstract protected function doContains($id);

    /**
     * Puts data into the cache.
     *
     * @param string $id       The cache id.
     * @param string $data     The cache entry/data.
     * @param int    $lifeTime The lifetime. If != 0, sets a specific lifetime for this
     *                           cache entry (0 => infinite lifeTime).
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    abstract protected function doSave($id, $data, $lifeTime = 0);

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    abstract protected function doDelete($id);

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    abstract protected function doFlush();

    /**
     * Retrieves cached information from the data store.
     *
     * @since 2.2
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    abstract protected function doGetStats();
}
