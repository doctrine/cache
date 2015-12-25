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
 * Array cache driver.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author David Abdemoulaie <dave@hobodave.com>
 */
class ArrayCache extends CacheProvider
{
    /**
     * @var array $data
     */
    private $data = array();

    /**
     * @var array $stats
     */
    private $stats = array();

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->stats = array(
            Cache::STATS_HITS => 0,
            Cache::STATS_MISSES => 0,
            Cache::STATS_UPTIME => time(),
            Cache::STATS_MEMORY_USAGE => null,
            Cache::STATS_MEMORY_AVAILABLE => null,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        if ($this->doContains($id)) {
            $this->stats[Cache::STATS_HITS]++;
            return $this->data[$id][0];
        }
        $this->stats[Cache::STATS_MISSES]++;
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        // isset() is required for performance optimizations, to avoid unnecessary function calls to array_key_exists.
        if (isset($this->data[$id])) {
            $ttl = $this->data[$id][1];
            if ($ttl !== false && $ttl < time()) {
                $this->doDelete($id);
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $ttl = $lifeTime > 0 ? time()+$lifeTime : false;
        $this->data[$id] = array($data, $ttl);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        unset($this->data[$id]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $this->data = array();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        return $this->stats;
    }
}
