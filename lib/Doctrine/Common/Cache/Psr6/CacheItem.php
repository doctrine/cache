<?php

namespace Doctrine\Common\Cache\Psr6;

use Psr\Cache\CacheItemInterface;

final class CacheItem implements CacheItemInterface
{
    /** @var string */
    private $key;
    /** @var mixed */
    private $value;
    /** @var bool */
    private $hit;
    /** @var \DateTimeInterface|null */
    private $expiration;

    public function __construct(string $key, $data)
    {
        $this->key = $key;
        $this->value = false === $data ? null : $data;
        $this->hit = false !== $data;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * @inheritDoc
     */
    public function set($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt($expiration): self
    {
        if (null !== $expiration && !$expiration instanceof \DateTimeInterface) {
            throw new \TypeError(sprintf('Expected $expiration to be an instance of DateTimeInterface or null, got %s', \is_object($expiration) ? \get_class($expiration) : \gettype($expiration)));
        }

        $this->expiration = $expiration;

        return $this;
    }
    /**
     * @inheritDoc
     */
    public function expiresAfter($time): self
    {
        if (null === $time) {
            $this->expiration = null;
        } elseif (is_numeric($time)) {
            $this->expiration = new \DateTimeImmutable(sprintf('now +%d seconds', $time));
        } elseif ($time instanceof \DateInterval) {
            $this->expiration = (new \DateTimeImmutable())->add($time);
        } else {
            throw new \TypeError(sprintf('Expected $time to be either an integer, an instance of DateInterval or null, got %s', \is_object($time) ? \get_class($time) : \gettype($time)));
        }

        return $this;
    }

    public function getExpiration(): ?\DateTimeInterface
    {
        return $this->expiration;
    }
}
