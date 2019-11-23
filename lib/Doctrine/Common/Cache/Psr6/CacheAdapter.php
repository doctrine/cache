<?php

namespace Doctrine\Common\Cache\Psr6;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\MultiOperationCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class CacheAdapter implements CacheItemPoolInterface
{
    /** @var Cache|ClearableCache|MultiOperationCache */
    private $cache;

    /**
     * @var CacheItemInterface[]
     */
    private $deferredItems = [];

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function getItem($key): CacheItemInterface
    {
        $this->assertValidKey($key);

        if (isset($this->deferredItems[$key])) {
            return new CacheItem($key, $this->deferredItems[$key]->get());
        }

        return new CacheItem($key, $this->cache->fetch($key));
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = []): iterable
    {
        $this->assertValidKeys($keys);

        $fetchedValues = $this->cache->fetchMultiple($keys);
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = new CacheItem($key, isset($this->deferredItems[$key]) ? $this->deferredItems[$key]->get() : (\array_key_exists($key, $fetchedValues) ? $fetchedValues[$key] : false));
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key): bool
    {
        $this->assertValidKey($key);

        return isset($this->deferredItems[$key])
            ? !$this->isExpired($this->deferredItems[$key])
            : $this->cache->contains($key)
        ;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->deferredItems = [];

        return $this->cache->deleteAll();
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key): bool
    {
        $this->assertValidKey($key);
        unset($this->deferredItems[$key]);

        return $this->cache->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys): bool
    {
        $this->assertValidKeys($keys);

        foreach ($keys as $key) {
            unset($this->deferredItems[$key]);
        }

        return $this->cache->deleteMultiple($keys);
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item): bool
    {
        unset($this->deferredItems[$item->getKey()]);

        if ($this->isExpired($item)) {
            return $this->cache->delete($item->getKey());
        }

        return $this->cache->save($item->getKey(), $item->get(), !$item instanceof CacheItem || null === $item->getExpiration() ? 0 : $item->getExpiration()->getTimestamp() - time());
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        if ($this->isExpired($item)) {
            return $this->cache->delete($item->getKey());
        }

        $this->deferredItems[$item->getKey()] = $item;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function commit(): bool
    {
        $success =  $this->cache->saveMultiple(array_map(
            static function (CacheItemInterface $cacheItem) {
                return $cacheItem->get();
            },
            $this->deferredItems
        ));

        if ($success) {
            $this->deferredItems = [];
        }

        return $success;
    }

    public function __destruct()
    {
        $this->commit();
    }

    private function assertValidKey($key): void
    {
        if (!\is_string($key) || '' === $key || preg_match('#[\{\}\(\)/\\\\@:]#', $key)) {
            throw new InvalidArgumentException('Invalid cache key.');
        }
    }

    private function assertValidKeys(iterable $keys): void
    {
        foreach ($keys as $key) {
            $this->assertValidKey($key);
        }
    }

    private function isExpired(CacheItemInterface $item): bool
    {
        return $item instanceof CacheItem
            && null !== $item->getExpiration()
            && $item->getExpiration() < new \DateTimeImmutable()
        ;
    }
}
