<?php

namespace Doctrine\Common\Filter;

use Doctrine\Common\Filter\Hash\Hash;
use Doctrine\Common\Filter\Persist\BitPersister;
use Doctrine\Common\Filter\Persist\CountPersister;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
final class CountingBloomFilter extends BloomFilterAbstract implements DeletableFilter
{
    /** @var CountPersister */
    private $countPersister;

    /**
     * @param BitPersister $bitPersister
     * @param CountPersister $countPersister
     * @param Hash $hash
     */
    public function __construct(BitPersister $bitPersister, CountPersister $countPersister, Hash $hash)
    {
        $this->countPersister = $countPersister;
        parent::__construct($bitPersister, $hash);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $value)
    {
        $this->currentSetSize--;
        $bits = $this->getBits($value);

        foreach ($bits as $bit) {
            $this->countPersister->decrementBit($bit);
        }
        $this->persister->unsetBulk($bits);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function deleteBulk(array $valueList)
    {
        $bits = [];
        $this->currentSetSize -= count($valueList);
        foreach ($valueList as $value) {
            $bits[] = $this->getBits($value);
        }

        $bits = call_user_func_array('array_merge', $bits);

        foreach ($bits as $bit) {
            $this->countPersister->decrementBit($bit);
        }

        $this->persister->unsetBulk($bits);

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function doAdd(string $value)
    {
        $bits = $this->getBits($value);
        foreach ($bits as $bit) {
            $this->countPersister->incrementBit($bit);
        }

        $this->persister->setBulk($bits);

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

        $bits = call_user_func_array('array_merge', $bits);

        $this->persister->setBulk($bits);
        $this->countPersister->incrementBulk($bits);

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
