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
 * Interface for cache that can be marked and invalidates bu tags.
 *
 * @link   www.doctrine-project.org
 * @since  1.6
 * @author Artem Ryzhkov <artem@smart-core.org>
 */
interface TaggableCache
{
    /**
     * Puts data into the cache and mark enty with tags.
     *
     * If a cache entry with the given id already exists, its data will be replaced.
     *
     * @param string $id       The cache id.
     * @param mixed  $data     The cache entry/data.
     * @param int    $lifeTime The lifetime in number of seconds for this cache entry.
     *                         If zero (the default), the entry never expires (although it may be deleted from the cache
     *                         to make place for other entries).
     * @param array $tags
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function saveWithTags($id, $data, $lifeTime = 0, array $tags = []);

    /**
     * Fetches an entry tags from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed The array with tags or FALSE, if no cache entry exists for the given id.
     */
    public function fetchTags($id);

    /**
     * Fetches an entry by tag from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetchByTag($tag);

    /**
     * Deletes a cache entry marked by tag.
     *
     * @param string $tag The tag id.
     *
     * @return bool TRUE if the cache entry tag was successfully deleted, FALSE otherwise.
     *              Deleting a non-existing entry is considered successful.
     */
    public function deleteByTag($tag);

    /**
     * Deletes a cache entries marked by tags.
     *
     * @param array $tag The tag id's.
     */
    public function deleteByTags(array $tags = []);
}
