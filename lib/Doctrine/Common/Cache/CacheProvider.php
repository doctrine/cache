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
abstract class CacheProvider implements Cache, FlushableCache, ClearableCache, MultiGetCache, MultiPutCache
{
    const DOCTRINE_NAMESPACE_CACHEKEY = 'DoctrineNamespaceCacheKey[%s]';

    /**
     * The namespace to prefix all cache ids with.
     *
     * @var string
     */
    private $namespace = '';

    /**
     * The namespace version.
     *
     * @var integer|null
     */
    private $namespaceVersion;

    /**
     * Sets the namespace to prefix all cache ids with.
     *
     * @param string $namespace
     *
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->namespace        = (string) $namespace;
        $this->namespaceVersion = null;
    }

    /**
     * Retrieves the namespace that prefixes all cache ids.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        return $this->doFetch($this->getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMultiple(array $keys)
    {
        if (empty($keys)) {
            return [];
        }

        // note: the array_combine() is in place to keep an association between our $keys and the $namespacedKeys
        $namespacedKeys = array_combine($keys, array_map([$this, 'getNamespacedId'], $keys));
        $items          = $this->doFetchMultiple($namespacedKeys);
        $foundItems     = [];

        // no internal array function supports this sort of mapping: needs to be iterative
        // this filters and combines keys in one pass
        foreach ($namespacedKeys as $requestedKey => $namespacedKey) {
            if (isset($items[$namespacedKey]) || array_key_exists($namespacedKey, $items)) {
                $foundItems[$requestedKey] = $items[$namespacedKey];
            }
        }

        return $foundItems;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchByTags(array $tags = array())
    {
        $nsTags = array();
        foreach ($tags as $key => $value) {
            $nsTag = is_string($key) ? $key.':'.$value : $value;
            $nsTags[] = $this->getNamespacedTag($nsTag);
        }

        $nsValues = $this->doFetchByTags($nsTags);

        $values = array();
        foreach ($nsValues as $nsId => $value) {
            $id = $this->getOriginalId($nsId);

            $values[$id] = $value;
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function saveMultiple(array $keysAndValues, $lifetime = 0)
    {
        $namespacedKeysAndValues = [];
        foreach ($keysAndValues as $key => $value) {
            $namespacedKeysAndValues[$this->getNamespacedId($key)] = $value;
        }

        return $this->doSaveMultiple($namespacedKeysAndValues, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->doContains($this->getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->doSave($this->getNamespacedId($id), $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function tag($id, array $tags = [])
    {
        $nsId = $this->getNamespacedId($id);

        $nsTags = array();
        foreach ($tags as $key => $value) {
            $tag = is_string($key) ? $key.':'.$value : $value;
            $nsTags[] = $this->getNamespacedTag($tag);
        }

        $this->doTag($nsId, $nsTags);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->doDelete($this->getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByTags(array $tags = array())
    {
        $nsTags = array();
        foreach ($tags as $key => $value) {
            $tag = is_string($key) ? $key.':'.$value : $value;
            $nsTags[] = $this->getNamespacedTag($tag);
        }

        return $this->doDeleteByTags($nsTags);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return $this->doGetStats();
    }

    /**
     * {@inheritDoc}
     */
    public function flushAll()
    {
        return $this->doFlush();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAll()
    {
        $namespaceCacheKey = $this->getNamespaceCacheKey();
        $namespaceVersion  = $this->getNamespaceVersion() + 1;

        if ($this->doSave($namespaceCacheKey, $namespaceVersion)) {
            $this->namespaceVersion = $namespaceVersion;

            return true;
        }

        return false;
    }

    /**
     * Prefixes the passed id with the configured namespace value.
     *
     * @param string $id The id to namespace.
     *
     * @return string The namespaced id.
     */
    private function getNamespacedId($id)
    {
        $namespaceVersion  = $this->getNamespaceVersion();

        return sprintf('%s[%s][%s]', $this->namespace, $id, $namespaceVersion);
    }

    /**
     * @param string $nsId
     *
     * @return string
     */
    private function getOriginalId($nsId)
    {
        static $regex;
        if (empty($regex)) {
            $regex = $this->getNamespacedId('#ID#');
            $regex = '/'.preg_quote($regex).'/';
            $regex = str_replace('#ID#', '(?P<id>[^]]++)', $regex);
        }

        preg_match($regex, $nsId, $m);

        if (isset($m['id'])) {
            return $m['id'];
        }
    }

    /**
     * Returns the namespace cache key.
     *
     * @return string
     */
    private function getNamespaceCacheKey()
    {
        return sprintf(self::DOCTRINE_NAMESPACE_CACHEKEY, $this->namespace);
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    private function getNamespacedTag($tag)
    {
        $namespaceVersion = $this->getNamespaceVersion();

        return sprintf('%sTags[%s][%s]', $this->namespace, $tag, $namespaceVersion);
    }

    /**
     * Returns the namespace version.
     *
     * @return integer
     */
    private function getNamespaceVersion()
    {
        if (null !== $this->namespaceVersion) {
            return $this->namespaceVersion;
        }

        $namespaceCacheKey = $this->getNamespaceCacheKey();
        $this->namespaceVersion = $this->doFetch($namespaceCacheKey) ?: 1;

        return $this->namespaceVersion;
    }

    /**
     * Default implementation of doFetchMultiple. Each driver that supports multi-get should owerwrite it.
     *
     * @param array $keys Array of keys to retrieve from cache
     * @return array Array of values retrieved for the given keys.
     */
    protected function doFetchMultiple(array $keys)
    {
        $returnValues = [];

        foreach ($keys as $key) {
            if (false !== ($item = $this->doFetch($key)) || $this->doContains($key)) {
                $returnValues[$key] = $item;
            }
        }

        return $returnValues;
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed|false The cached data or FALSE, if no cache entry exists for the given id.
     */
    abstract protected function doFetch($id);


    /**
     * @param array|string[] $tags
     *
     * @return array|mixed[]
     */
    protected function doFetchByTags(array $tags = array())
    {
        $idLists = $this->doFetchMultiple($tags);
        foreach ($idLists as &$idList) {
            $idList = explode(';', $idList);
            $idList = array_unique($idList);
        }

        if (empty($idLists)) {
            return array();
        }

        $ids = call_user_func_array('array_intersect', $idLists);

        return $this->doFetchMultiple($ids);
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    abstract protected function doContains($id);

    /**
     * Default implementation of doSaveMultiple. Each driver that supports multi-put should override it.
     *
     * @param array $keysAndValues  Array of keys and values to save in cache
     * @param int   $lifetime       The lifetime. If != 0, sets a specific lifetime for these
     *                              cache entries (0 => infinite lifeTime).
     *
     * @return bool TRUE if the operation was successful, FALSE if it wasn't.
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {
        $success = true;

        foreach ($keysAndValues as $key => $value) {
            if (!$this->doSave($key, $value, $lifetime)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Puts data into the cache.
     *
     * @param string $id       The cache id.
     * @param string $data     The cache entry/data.
     * @param int    $lifeTime The lifetime. If != 0, sets a specific lifetime for this
     *                           cache entry (0 => infinite lifeTime).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    abstract protected function doSave($id, $data, $lifeTime = 0);

    /**
     * @param string $id
     * @param array|string[] $tags
     */
    protected function doTag($id, array $tags = [])
    {
        foreach ($tags as $tag) {
            $set = (string)$this->doFetch($tag);
            $set = explode(';', $set);
            $set[] = $id;
            $set = array_unique($set);
            $set = implode(';', $set);
            $this->doSave($tag, $set);
        }
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    abstract protected function doDelete($id);

    /**
     * @param array|string[] $tags
     *
     * @return bool
     */
    protected function doDeleteByTags(array $tags = array())
    {
        $idLists = $this->doFetchMultiple($tags);
        foreach ($idLists as &$idList) {
            $idList = explode(';', $idList);
            $idList = array_unique($idList);
        }

        if (empty($idLists)) {
            return true;
        }

        $ids = call_user_func_array('array_intersect', $idLists);

        foreach ($ids as $id) {
            return $this->doDelete($id);
        }
    }

    /**
     * Flushes all cache entries.
     *
     * @return bool TRUE if the cache entries were successfully flushed, FALSE otherwise.
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
