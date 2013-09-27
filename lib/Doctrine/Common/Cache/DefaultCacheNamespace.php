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
 * CacheNamespace compotible whit all doctrine cache drivers
 *
 * @since  1.3
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultCacheNamespace implements CacheNamespace
{
    const DOCTRINE_NAMESPACE_CACHEKEY = 'doctrine_cache_ns_version_';

    /**
     * The namespace to prefix all cache keys with.
     *
     * @var string
     */
    protected $namespace;

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    protected $cache;

    /**
     * The namespace version.
     *
     * @var string
     */
    protected $version;

    /**
     * The key to store the namespace version.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * @var string
     */
    protected $format = '%s_%s_%s';

    /**
     * @param \Doctrine\Common\Cache\CacheProvider $cache
     * @param string                               $namespace
     */
    public function __construct(CacheProvider $cache, $namespace = 'doctrine_cache')
    {
        $this->cache = $cache;

        $this->setNamespace($namespace);
    }

    /**
     * Sets the key to store the namespace version number.
     *
     * @param string $cacheKey
     */
    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    /**
     * Set the format string for the namespaced cache keys.
     * It should contains The 3 directives : namespace, key and version number.
     *
     * <code>
     *  <?php
     *  $cacheNamespace->setNamespace('my_ns');
     *  $cacheNamespace->setFormat('%s.%s.%s');
     *
     *  echo $cacheNamespace->getNamespacedKey('foo');
     *  // my_ns.foo.1
     *
     *  $cacheNamespace->setNamespace('my_ns');
     *  $cacheNamespace->setFormat('%s[%s][%s]');
     * 
     *  echo $cacheNamespace->getNamespacedKey('foo');
     *  // my_ns[foo][1]
     *  ?>
     * </code>
     *
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Gets the format string for the namespaced cache keys.
     *
     * @return type
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace)
    {
        $this->version   = null;
        $this->namespace = $namespace;
        $this->cacheKey  = self::DOCTRINE_NAMESPACE_CACHEKEY . $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespacedKey($key)
    {
        return sprintf($this->format, $this->namespace, $key, $this->version ?: $this->getVersion());
    }

    /**
     * {@inheritdoc}
     */
    public function increment()
    {
        $version = $this->getVersion() + 1;

        if ($this->cache->saveUsingRealKey($this->cacheKey, $version)) {
            $this->version = $version;

            return true;
        }

        return false;
    }

    /**
     * Returns the current namespace version.
     *
     * @return string
     */
    protected function getVersion()
    {
        if ($this->version === null) {
            $this->version = $this->fetchVersion();
        }

        return $this->version;
    }

    /**
     * Fetchs the namespace version from cache.
     *
     * @return integer
     */
    protected function fetchVersion()
    {
        $version = $this->cache->fetchUsingRealKey($this->cacheKey);

        if ($version === false) {
            $version = 1;

            $this->cache->saveUsingRealKey($this->cacheKey, $version);
        }

        return $version;
    }
}
