<?php

namespace Doctrine\Common\Cache;

interface AtomicFetchCache
{
    /**
     * Atomically fetches an item from the cache, or stores and returns the data from the generator
     *
     * @return mixed
     */
    public function fetchAtomic(string $id, callable $generator, int $ttl = 0);
}
