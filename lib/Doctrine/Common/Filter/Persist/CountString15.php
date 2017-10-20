<?php

namespace Doctrine\Common\Filter\Persist;

use Doctrine\Common\Filter\Exception\MaxLimitPerBitReached;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class CountString15 extends CountStringAbstract
{
    const MAX_AMOUNT_PER_BIT = 15;

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

        $low = $byte & 0x0F;
        $high = ($byte >> 4) & 0x0F;

        if ($bit & 1) {
            $return = ++$high;
        } else {
            $return = ++$low;
        }

        if ($low > self::MAX_AMOUNT_PER_BIT || $high > self::MAX_AMOUNT_PER_BIT) {
            throw new MaxLimitPerBitReached('max amount per bit should not be higher than ' . self::MAX_AMOUNT_PER_BIT);
        }

        $this->bytes[$offsetByte] = chr($low | ($high << 4));

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function get(int $bit): int
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $low = $byte & 0x0F;
        $high = ($byte >> 4) & 0x0F;

        if ($bit & 1) {
            return $high;
        } else {
            return $low;
        }
    }

    /**
     * @inheritdoc
     */
    public function decrementBit(int $bit): int
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $low = $byte & 0x0F;
        $high = ($byte >> 4) & 0x0F;

        if ($bit & 1) {
            $return = --$high;
        } else {
            $return = --$low;
        }

        $this->bytes[$offsetByte] = chr(max([$low, 0]) | (max([$high, 0]) << 4));

        return max([$return, 0]);
    }

    /**
     * @param int $offset
     * @return int
     */
    protected function offsetToByte(int $offset): int
    {
        $byte = $offset / 2;
        $this->checkSize($byte);

        return $byte;
    }
}
