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
 * Interface for cache drivers.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface CacheMultiple
{
    /**
     * Fetches multiple entries from the cache.
     *
     * @param array $ids Array of cache IDs to fetch.
     *
     * @return array Array of cached data.
     */
    function fetchMany(array $ids);

    /**
     * Tests if multiple entries exist in the cache.
     *
     * @param array $ids Array of cache IDs to check for.
     *
     * @return boolean TRUE if all cache entry IDs exist, FALSE otherwise.
     */
    function containsMany(array $ids);

    /**
     * Writes multiple data entries into the cache.
     *
     * @param array $entries Array of entries. Each entry is an array of:
     *      - string $id       The cache id.
     *      - mixed  $data     The cache entry/data.
     *      - int    $lifeTime The cache lifetime.
     *                         If != 0, sets a specific lifetime for this cache entry (0 => infinite lifeTime).
     *
     * @return boolean TRUE if all entries were successfully stored in the cache, FALSE otherwise.
     */
    function saveMany(array $entries);

    /**
     * Deletes all cache entries.
     *
     * @param array $ids Array of cache IDs to delete.
     *
     * @return boolean TRUE if the cache entries were successfully deleted, FALSE otherwise.
     */
    function deleteMany(array $ids);

    /**
     * Deletes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully deleted, FALSE otherwise.
     */
    function deleteAll();

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    function flushAll();
}
