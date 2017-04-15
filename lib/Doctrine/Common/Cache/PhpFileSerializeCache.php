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
 * Caches objects as php files (which can be optimized by an optcode cache)
 * similar to PhpFileCache. However, objects are serialized, so don't need to
 * support var_export and __set_state, as required by PhpFileCache.
 *
 * @author Tim Roediger <superdweebie@gmail.com>
 */
class PhpFileSerializeCache extends FileCache
{
    const EXTENSION = '.doctrinecache.php';

    /**
     * {@inheritdoc}
     */
    protected $extension = self::EXTENSION;

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {

        $filename = $this->getFilename($id);
        $value = @include $filename;
        if (!isset($value)){
            return false;
        }

        $lifetime = (integer) $value['lifetime'];
        if ($lifetime !== 0 && $lifetime < time()) {
            return false;
        }
        return unserialize($value['data']);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $filename = $this->getFilename($id);
        $value = @include $filename;
        if (!isset($value)){
            return false;
        }

        $lifetime = $value['lifetime'];

        return $lifetime === 0 || $lifetime > time();
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifetime = 0)
    {
        if ($lifetime > 0) {
            $lifetime = time() + $lifetime;
        }

        $filename   = $this->getFilename($id);
        $filepath   = pathinfo($filename, PATHINFO_DIRNAME);

        if ( ! is_dir($filepath)) {
            mkdir($filepath, 0777, true);
        }

        $value = [
            'lifetime' => $lifetime,
            'format' => 'standard',
            'data' => serialize($data)
        ];
        return file_put_contents($filename, sprintf('<?php return %s;', var_export($value, true)));
    }
}