<?php

namespace Doctrine\Common\Filter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class BloomFilter extends BloomFilterAbstract
{
    /**
     * @inheritdoc
     */
    protected function doAdd(string $value)
    {
        $this->persister->setBulk($this->getBits($value));

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function doAddBulk(array $valueList)
    {
        $bits = [];
        foreach ($valueList as $value) {
            $bits[] = $this->getBits($value);
        }

        $this->persister->setBulk(call_user_func_array('array_merge', $bits));

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function doHas(string $value): bool
    {
        $bits = $this->persister->getBulk($this->getBits($value));

        return !in_array(0, $bits);
    }
}
