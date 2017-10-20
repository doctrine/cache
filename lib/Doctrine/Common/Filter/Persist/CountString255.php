<?php

namespace Doctrine\Common\Filter\Persist;

use Doctrine\Common\Filter\Exception\MaxLimitPerBitReached;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class CountString255 extends CountStringAbstract
{
    const MAX_AMOUNT_PER_BIT = 255;

    /**
     * @inheritdoc
     */
    public function incrementBulk(array $bits): array
    {
        $result = [];
        foreach ($bits as $bit) {
            $result[$bit] = $this->incrementBit($bit);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function incrementBit(int $bit): int
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $byte++;

        if ($byte > self::MAX_AMOUNT_PER_BIT) {
            throw new MaxLimitPerBitReached('max amount per bit should not be higher than ' . self::MAX_AMOUNT_PER_BIT);
        }

        $this->bytes[$offsetByte] = chr($byte);

        return $byte;
    }

    /**
     * @inheritdoc
     */
    public function get(int $bit): int
    {
        $offsetByte = $this->offsetToByte($bit);
        return ord($this->bytes[$offsetByte]);
    }

    /**
     * @inheritdoc
     */
    public function decrementBit(int $bit): int
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $byte = max([--$byte, 0]);

        $this->bytes[$offsetByte] = chr($byte);

        return $byte;
    }

    /**
     * @param int $offset
     * @return int
     */
    protected function offsetToByte(int $offset): int
    {
        $this->checkSize($offset);

        return $offset;
    }
}
