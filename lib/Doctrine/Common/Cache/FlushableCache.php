<?php

namespace Doctrine\Common\Cache;

/**
 * Interface for cache that can be flushed.
 *
 * @link   www.doctrine-project.org
 * @since  1.4
 * @author Adirelle <adirelle@gmail.com>
 */
interface FlushableCache
{
    /**
     * Flushes all cache entries, globally.
     *
     * @return bool TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll();
}
