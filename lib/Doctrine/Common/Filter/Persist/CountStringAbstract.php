<?php

namespace Doctrine\Common\Filter\Persist;

use Doctrine\Common\Filter\Exception\InvalidValue;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
abstract class CountStringAbstract implements CountPersister
{
    const DEFAULT_BYTE_SIZE = 1024;

    /** @var string */
    protected $bytes;
    /** @var int */
    protected $size;

    /**
     * @param string|null $str
     */
    public function __construct(string $str = null)
    {
        if ($str === null) {
            $this->size = static::DEFAULT_BYTE_SIZE;
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
        $this->size = static::DEFAULT_BYTE_SIZE;
        $this->bytes = str_repeat(chr(0), $this->size);
    }


    /**
     * @param int $value
     */
    protected function assertOffset(int $value)
    {
        if ($value < 0) {
            throw new InvalidValue('Value must be greater than zero.');
        }
    }

    /**
     * @param int $byte
     */
    protected function checkSize(int $byte)
    {
        if ($this->size <= $byte) {
            $this->bytes .= str_repeat(chr(0), $byte - $this->size + static::DEFAULT_BYTE_SIZE);
            $this->size = strlen($this->bytes);
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->bytes;
    }
}
