<?php

namespace Doctrine\Common\Filter\Persist;

use Doctrine\Common\Filter\Exception\InvalidCounter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class CountRedis implements CountPersister
{
    /** @var string */
    private $key;
    /** @var \Redis */
    private $redis;

    /**
     * @param \Redis $redis
     * @param string $key
     */
    public function __construct(\Redis $redis, $key)
    {
        $this->key = $key;
        $this->redis = $redis;
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->redis->del($this->key);
    }

    /**
     * @inheritdoc
     */
    public function decrementBit(int $bit): int
    {
        $result = $this->redis->hIncrBy($this->key, $bit, -1);
        if ($result < 0) {
            $this->redis->hSet($this->key, $bit, 0);
            throw new InvalidCounter(
                sprintf(
                    'Redis key [%s] had invalid count[%s] for the bit [%s]. Has been set to 0',
                    $this->key,
                    $result,
                    $bit
                )
            );
        }

        return max([0, $result]);
    }

    /**
     * @inheritdoc
     */
    public function incrementBit(int $bit): int
    {
        return $this->redis->hIncrBy($this->key, $bit, 1);
    }

    /**
     * @inheritdoc
     */
    public function incrementBulk(array $bits): array
    {
        $pipe = $this->redis->pipeline();

        $result = [];

        foreach ($bits as $bit) {
            $result[$bit] = $pipe->hIncrBy($this->key, $bit, 1);
        }

        $pipe->exec();

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function get(int $bit): int
    {
        return $this->redis->hGet($this->key, $bit);
    }
}