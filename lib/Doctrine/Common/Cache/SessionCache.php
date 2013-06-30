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
 * Native session cache driver.
 *
 * @link   www.doctrine-project.org
 * @since  2.3
 * @author Gusakov Nikita <gusakov.nik@gmail.com>
 */
class SessionCache extends CacheProvider
{
    const CACHE_KEY = 'DOCTRINE_CACHE_DATA';

    public function getNamespace()
    {
        $parent = parent::getNamespace();

        if ($parent === '') {
            return self::CACHE_KEY;
        }

        return $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        return $this->doFlush();
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $namespace = $this->getNamespace();

        return isset($_SESSION[$namespace][$id]) ? $_SESSION[$namespace][$id][1] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        $namespace = $this->getNamespace();

        if (!isset($_SESSION[$namespace][$id])) {
            return false;
        }

        $lifetime = $_SESSION[$namespace][$id][0];

        if ($lifetime !== 0 && $lifetime < time()) {
            unset($_SESSION[$namespace][$id]);

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }

        $_SESSION[$this->getNamespace()][$id] = array($lifeTime, $data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        unset($_SESSION[$this->getNamespace()][$id]);

        return true;
    }

    /**
     * @see fetch
     */
    protected function doFetch($id)
    {
    }

    /**
     * @see contains
     */
    protected function doContains($id)
    {
    }

    /**
     * @see save
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
    }

    /**
     * @see delete
     */
    protected function doDelete($id)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        unset($_SESSION[$this->getNamespace()]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        return null;
    }
}
