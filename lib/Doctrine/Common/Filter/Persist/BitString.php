<?php

namespace Doctrine\Common\Filter\Persist;

use Doctrine\Common\Filter\Exception\InvalidValue;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class BitString implements BitPersister
{
    const DEFAULT_BYTE_SIZE = 1024;

    /** @var string */
    private $bytes;
    /** @var int */
    private $size;

    /**
     * @param string|null $str
     */
    public function __construct(string $str = null)
    {
        if ($str === null) {
            $this->size = self::DEFAULT_BYTE_SIZE;
            $this->bytes = str_repeat(chr(0), $this->size);
        } else {
            $this->size = strlen($str);
            $this->bytes = $str;
        }
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->size = self::DEFAULT_BYTE_SIZE;
        $this->bytes = str_repeat(chr(0), $this->size);
    }

    /**
     * @inheritdoc
     */
    public function getBulk(array $bits): array
    {
        $resultBits = [];
        foreach ($bits as $bit) {
            $resultBits[] = $this->get($bit);
        }

        return $resultBits;
    }

    /**
     * @inheritdoc
     */
    public function unsetBulk(array $bits)
    {
        foreach ($bits as $bit) {
            $this->unset($bit);
        }
    }

    /**
     * @inheritdoc
     */
    public function unset(int $bit)
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $byte &= ~(1 << $bit % 8);
        $this->bytes[$offsetByte] = chr($byte);
    }

    /**
     * @inheritdoc
     */
    public function setBulk(array $bits)
    {
        foreach ($bits as $bit) {
            $this->set($bit);
        }
    }

    /**
     * @inheritdoc
     */
    public function get(int $bit): int
    {
        $byte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$byte]);

        return ($byte >> $bit % 8) & 1;
    }

    /**
     * @inheritdoc
     */
    public function set(int $bit)
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $byte |= 1 << $bit % 8;
        $this->bytes[$offsetByte] = chr($byte);
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

    /**
     * @param int $offset
     * @return int
     */
    private function offsetToByte(int $offset): int
    {
        $this->assertOffset($offset);
        $byte = $offset >> 0x3;

        if ($this->size <= $byte) {
            $this->bytes .= str_repeat(chr(0), $byte - $this->size + self::DEFAULT_BYTE_SIZE);
            $this->size = strlen($this->bytes);
        }

        return $byte;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->bytes;
    }
}
