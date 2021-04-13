<?php

namespace Doctrine\Common\Cache\Psr6;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\MultiOperationCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\DoctrineProvider as SymfonyDoctrineProvider;

use function array_key_exists;
use function assert;
use function count;
use function current;
use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function microtime;
use function sprintf;
use function strpbrk;

final class CacheAdapter implements CacheItemPoolInterface
{
    private const RESERVED_CHARACTERS = '{}()/\@:';

    /** @var Cache|ClearableCache|MultiOperationCache */
    private $cache;

    /** @var CacheItem[] */
    private $deferredItems = [];

    public static function wrap(Cache $cache): CacheItemPoolInterface
    {
        if ($cache instanceof DoctrineProvider) {
            return $cache->getPool();
        }

        if ($cache instanceof SymfonyDoctrineProvider) {
            $getPool = function () {
                // phpcs:ignore Squiz.Scope.StaticThisUsage.Found
                return $this->pool;
            };

            return $getPool->bindTo($cache, SymfonyDoctrineProvider::class)();
        }

        return new self($cache);
    }

    private function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /** @internal */
    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * {@inheritDoc}
     */
    public function getItem($key): CacheItemInterface
    {
        assert(self::validKey($key));

        if (isset($this->deferredItems[$key])) {
            $this->commit();
        }

        $value = $this->cache->fetch($key);

        if ($value !== false) {
            return new CacheItem($key, $value, true);
        }

        return new CacheItem($key, null, false);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = []): array
    {
        if ($this->deferredItems) {
            $this->commit();
        }

        assert(self::validKeys($keys));

        $values = $this->cache->fetchMultiple($keys);
        $items  = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $values)) {
                $items[$key] = new CacheItem($key, $values[$key], true);
            } else {
                $items[$key] = new CacheItem($key, null, false);
            }
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem($key): bool
    {
        assert(self::validKey($key));

        if (isset($this->deferredItems[$key])) {
            $this->commit();
        }

        return $this->cache->contains($key);
    }

    public function clear(): bool
    {
        $this->deferredItems = [];

        return $this->cache->deleteAll();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem($key): bool
    {
        assert(self::validKey($key));
        unset($this->deferredItems[$key]);

        return $this->cache->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            assert(self::validKey($key));
            unset($this->deferredItems[$key]);
        }

        return $this->cache->deleteMultiple($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->saveDeferred($item) && $this->commit();
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (! $item instanceof CacheItem) {
            return false;
        }

        $this->deferredItems[$item->getKey()] = $item;

        return true;
    }

    public function commit(): bool
    {
        if (! $this->deferredItems) {
            return true;
        }

        $now         = microtime(true);
        $itemsCount  = 0;
        $byLifetime  = [];
        $expiredKeys = [];

        foreach ($this->deferredItems as $key => $item) {
            $lifetime = ($item->getExpiry() ?? $now) - $now;

            if ($lifetime < 0) {
                $expiredKeys[] = $key;

                continue;
            }

            ++$itemsCount;
            $byLifetime[(int) $lifetime][$key] = $item->get();
        }

        switch (count($expiredKeys)) {
            case 0:
                break;
            case 1:
                $this->cache->delete(current($expiredKeys));
                break;
            default:
                $this->cache->deleteMultiple($expiredKeys);
                break;
        }

        if ($itemsCount === 1) {
            return $this->cache->save($key, $item->get(), $lifetime);
        }

        $success = true;
        foreach ($byLifetime as $lifetime => $values) {
            $success = $this->cache->saveMultiple($values, $lifetime) && $success;
        }

        return $success;
    }

    public function __destruct()
    {
        $this->commit();
    }

    /**
     * @param mixed $key
     */
    private static function validKey($key): bool
    {
        if (! is_string($key)) {
            throw new InvalidArgument(sprintf('Cache key must be string, "%s" given.', is_object($key) ? get_class($key) : gettype($key)));
        }

        if ($key === '') {
            throw new InvalidArgument('Cache key length must be greater than zero.');
        }

        if (strpbrk($key, self::RESERVED_CHARACTERS) !== false) {
            throw new InvalidArgument(sprintf('Cache key "%s" contains reserved characters "%s".', $key, self::RESERVED_CHARACTERS));
        }

        return true;
    }

    /**
     * @param mixed[] $keys
     */
    private static function validKeys(array $keys): bool
    {
        foreach ($keys as $key) {
            self::validKey($key);
        }

        return true;
    }
}
