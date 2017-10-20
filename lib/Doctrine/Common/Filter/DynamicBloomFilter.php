<?php

namespace Doctrine\Common\Filter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class DynamicBloomFilter extends BloomFilterAbstract
{
    /**
     * @inheritdoc
     */
    protected function doAdd(string $value)
    {
        $this->persister->setBulk($this->getBits($value, floor($this->currentSetSize / $this->setSize)));

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function doAddBulk(array $valueList)
    {
        $bits = [];
        $i = count($valueList);
        foreach ($valueList as $value) {
            $bits[] = $this->getBits($value, floor(($this->currentSetSize - $i--) / $this->setSize));
        }
        $this->persister->setBulk(call_user_func_array('array_merge', $bits));

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function doHas(string $value): bool
    {
        $this->assertInit();
        $bloomFilterCount = floor($this->currentSetSize / $this->setSize);
        $result = false;
        $bits = $this->getBits($value);

        for ($i = 0; $i <= $bloomFilterCount; ++$i) {
            $result = !in_array(
                0,
                $this->persister->getBulk(array_map(
                        function ($bit) use ($i) {
                            return $bit + ($i * $this->bitSize);
                        },
                        $bits
                    )
                )
            );

            if ($result) {
                return true;
            }
        }

        return $result;
    }
}
