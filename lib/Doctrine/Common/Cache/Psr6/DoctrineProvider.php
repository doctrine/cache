<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Common\Cache\Psr6;

use Doctrine\Common\Cache\CacheProvider;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\DoctrineAdapter as SymfonyDoctrineAdapter;
use Traversable;

use function array_combine;
use function array_filter;
use function array_keys;
use function array_map;
use function iterator_to_array;
use function rawurlencode;

/**
 * This class was copied from the Symfony Framework, see the original copyright
 * notice above. The code is distributed subject to the license terms in
 * https://github.com/symfony/symfony/blob/ff0cf61278982539c49e467db9ab13cbd342f76d/LICENSE
 */
final class DoctrineProvider extends CacheProvider
{
    /** @var CacheItemPoolInterface */
    private $pool;

    public static function wrap(CacheItemPoolInterface $pool): CacheProvider
    {
        if ($pool instanceof CacheAdapter) {
            return $pool->getCache();
        }

        if ($pool instanceof SymfonyDoctrineAdapter) {
            $getCache = function () {
                // phpcs:ignore Squiz.Scope.StaticThisUsage.Found
                return $this->provider;
            };

            return $getCache->bindTo($pool, SymfonyDoctrineAdapter::class)();
        }

        return new self($pool);
    }

    private function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /** @internal */
    public function getPool(): CacheItemPoolInterface
    {
        return $this->pool;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $item = $this->pool->getItem(rawurlencode($id));

        return $item->isHit() ? $item->get() : false;
    }

    /**
     * @inheritDoc
     */
    protected function doFetchMultiple(array $keys): array
    {
        $items = $this->pool->getItems(array_map('rawurlencode', $keys));
        if ($items instanceof Traversable) {
            $items = iterator_to_array($items);
        }

        $items = array_combine($keys, $items);
        $items = array_filter($items, static function (CacheItemInterface $item) {
            return $item->isHit();
        });

        return array_map(static function (CacheItemInterface $item) {
            return $item->get();
        }, $items);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    protected function doContains($id)
    {
        return $this->pool->hasItem(rawurlencode($id));
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $item = $this->pool->getItem(rawurlencode($id));

        if (0 < $lifeTime) {
            $item->expiresAfter($lifeTime);
        }

        return $this->pool->save($item->set($data));
    }

    /**
     * @inheritDoc
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0): bool
    {
        $keys = array_keys($keysAndValues);

        $items = $this->pool->getItems(array_map('rawurlencode', $keys));
        if ($items instanceof Traversable) {
            $items = iterator_to_array($items);
        }

        $items = array_combine($keys, $items);
        foreach ($items as $key => $item) {
            if (! $this->pool->saveDeferred($item->set($keysAndValues[$key]))) {
                return false;
            }
        }

        return $this->pool->commit();
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    protected function doDelete($id)
    {
        return $this->pool->deleteItem(rawurlencode($id));
    }

    /**
     * @inheritDoc
     */
    protected function doDeleteMultiple(array $keys): bool
    {
        return $this->pool->deleteItems(array_map('rawurlencode', $keys));
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    protected function doFlush()
    {
        return $this->pool->clear();
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    protected function doGetStats()
    {
        return null;
    }
}
