<?php

/*
 *  $Id$
 *
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
 * Filesystem cache driver.
 *
 * @since   2.3
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class FilesystemCache extends CacheProvider
{

    const EXTENSION = '.doctrinecache.php';

    /**
     * The cache directory.
     *
     * @var string
     */
    private $directory;

    /**
     * The cache file extension.
     *
     * @var string
     */
    private $extension;

    /**
     * @param string $directory The cache directory
     * @throws \InvalidArgumentException
     */
    public function __construct($directory, $extension = self::EXTENSION)
    {
        if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" does not exist and could not be created.',
                $directory
            ));
        }

        if (!is_writable($directory)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" is not writable.',
                $directory
            ));
        }

        $this->extension = $extension;
        $this->directory = realpath($directory);
    }

    /**
     * Gets the cache directory.
     * 
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Gets the cache file extension.
     * 
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    private function getFilename($id)
    {
        return $this->directory . DIRECTORY_SEPARATOR . $id . $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $filename = $this->getFilename($id);

        if ( ! file_exists($filename)) {
            return false;
        }

        $item = include $filename;
        if($item['lifetime'] !== 0 && $item['lifetime'] < time()) {
            return false;
        }

        return $item['data'];
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return file_exists($this->getFilename($id));
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }

        $item['data']       = $data;
        $item['lifetime']   = $lifeTime;
        $path               = $this->getFilename($id);

        return file_put_contents($path, '<?php return unserialize(' . var_export(serialize($item), true) . ');');
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return unlink($this->getFilename($id));
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        foreach (glob($this->directory . '/*' . $this->extension) as $file) {
            unlink($file);
        }

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