<?php

namespace Doctrine\Common\Filter\Persist;

use Doctrine\Common\Filter\Exception\InvalidValue;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class BitRedis implements BitPersister
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
    public function getBulk(array $bits): array
    {
        $pipe = $this->redis->pipeline();

        foreach ($bits as $bit) {
            $this->assertOffset($bit);
            $pipe->getBit($this->key, $bit);
        }

        return $pipe->exec();
    }

    /**
     * @inheritdoc
     */
    public function setBulk(array $bits)
    {
        $pipe = $this->redis->pipeline();

        foreach ($bits as $bit) {
            $this->assertOffset($bit);
            $pipe->setBit($this->key, $bit, 1);
        }

        $pipe->exec();
    }

    /**
     * @inheritdoc
     */
    public function unsetBulk(array $bits)
    {
        $pipe = $this->redis->pipeline();

        foreach ($bits as $bit) {
            $this->assertOffset($bit);
            $pipe->setBit($this->key, $bit, 0);
        }

        $pipe->exec();
    }

    /**
     * @inheritdoc
     */
    public function unset(int $bit)
    {
        $this->assertOffset($bit);
        $this->redis->setBit($this->key, $bit, 0);
    }

    /**
     * @inheritdoc
     */
    public function get(int $bit): int
    {
        $this->assertOffset($bit);
        return $this->redis->getBit($this->key, $bit);
    }

    /**
     * @inheritdoc
     */
    public function set(int $bit)
    {
        $this->assertOffset($bit);
        $this->redis->setBit($this->key, $bit, 1);
    }

    /**
     * @param int $value
     */
    private function assertOffset(int $value)
    {
        if ($value < 0) {
            throw new InvalidValue('Value must be greater than zero.');
        }
    }


}