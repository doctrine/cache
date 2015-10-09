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
 * Decorates another cache and adds a namespace to cache keys.
 *
 * This allows to avoid cache naming collisions when a global shared cache, like Redis, is used for different types of data
 * or for multiple applications. Introducing namespaces for non-shared caches like ArrayCache and FileCache with its own directory
 * or extension makes no sense.
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class NamespacedCacheDecorator implements Cache, FlushableCache, ClearableCache, MultiGetCache
{
    /**
     * @internal
     */
    const NAMESPACE_VERSION_KEY = 'DoctrineNamespaceVersion[%s]';

    /**
     * The namespace to prefix all cache ids with.
     *
     * @var string
     */
    private $namespace;

    /**
     * Under this cache id the current namespace version is saved.
     *
     * @var string
     */
    private $namespaceVersionKey;

    /**
     * @var Cache|FlushableCache|ClearableCache|MultiGetCache
     */
    private $cache;

    /**
     * Constructor.
     *
     * @param string $namespace The namespace to prefix all cache ids with.
     * @param Cache  $cache     The cache to decorate.
     *
     * @throws \InvalidArgumentException If the namespace is empty.
     */
    public function __construct($namespace, Cache $cache)
    {
        $this->namespace = (string) $namespace;

        if ('' === $this->namespace) {
            throw new \InvalidArgumentException('The namespace to prefix cache ids with must not be empty.');
        }

        $this->namespaceVersionKey = sprintf(self::NAMESPACE_VERSION_KEY, $this->namespace);
        $this->cache = $cache;
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
        return $this->cache->fetch($this->getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMultiple(array $keys)
    {
        $namespacedKeys = array_map(array($this, 'getNamespacedId'), $keys);
        $namespacedItems = $this->cache->fetchMultiple($namespacedKeys);
        $keyAssociation = array_combine($namespacedKeys, $keys);
        $items = array();

        foreach ($namespacedItems as $namespacedKey => $value) {
            $items[$keyAssociation[$namespacedKey]] = $value;
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->cache->contains($this->getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->cache->save($this->getNamespacedId($id), $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->cache->delete($this->getNamespacedId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return $this->cache->getStats();
    }

    /**
     * {@inheritDoc}
     */
    public function flushAll()
    {
        return $this->cache->flushAll();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAll()
    {
        $namespaceVersion = $this->getNamespaceVersion() + 1;

        return $this->cache->save($this->namespaceVersionKey, $namespaceVersion);
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
        $namespaceVersion = $this->getNamespaceVersion();

        return sprintf('%s[%s][%s]', $this->namespace, $id, $namespaceVersion);
    }

    /**
     * Returns the namespace version.
     *
     * @return int
     */
    private function getNamespaceVersion()
    {
        return $this->cache->fetch($this->namespaceVersionKey) ?: 1;
    }
}
