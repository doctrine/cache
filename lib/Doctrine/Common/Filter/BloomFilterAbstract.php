<?php

namespace Doctrine\Common\Filter;

use Doctrine\Common\Filter\Exception\InvalidValue;
use Doctrine\Common\Filter\Exception\NotInitialized;
use Doctrine\Common\Filter\Hash\Hash;
use Doctrine\Common\Filter\Persist\BitPersister;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
abstract class BloomFilterAbstract implements Filter, ResetableFilter
{
    const DEFAULT_PROBABILITY = 0.005;

    /** @var int */
    protected $bitSize;
    /** @var int */
    protected $hashCount;
    /** @var BitPersister */
    protected $persister;
    /** @var Hash */
    protected $hash;
    /** @var int */
    protected $setSize;
    /** @var float */
    protected $falsePositiveProbability;
    /** @var int */
    protected $currentSetSize;

    /**
     * @param BitPersister $persister
     * @param Hash $hash
     */
    public function __construct(BitPersister $persister, Hash $hash)
    {
        $this->persister = $persister;
        $this->hash = $hash;
        $this->falsePositiveProbability = static::DEFAULT_PROBABILITY;
        $this->currentSetSize = 0;
    }

    /**
     * @inheritdoc
     */
    public function has(string $value): bool
    {
        $this->assertInit();

        return $this->doHas($value);
    }

    /**
     * @inheritdoc
     */
    public function add(string $value)
    {
        $this->assertInit();
        $this->currentSetSize++;

        return $this->doAdd($value);
    }

    /**
     * @inheritdoc
     */
    public function addBulk(array $valueList)
    {
        $this->assertInit();
        $this->currentSetSize += count($valueList);

        return $this->doAddBulk($valueList);
    }

    /**
     * @param string $value
     * @return bool
     */
    abstract protected function doHas(string $value): bool;

    /**
     * @param array $valueList
     * @return $this
     */
    abstract protected function doAddBulk(array $valueList);

    /**
     * @param string $value
     * @return $this
     */
    abstract protected function doAdd(string $value);

    /**
     * @param int $setSize
     *
     * @return $this
     */
    public function setSize(int $setSize)
    {
        $this->setSize = (int) $setSize;
        $this->init();

        return $this;
    }

    /**
     * @param float $falsePositiveProbability
     *
     * @return $this
     */
    public function setFalsePositiveProbability(float $falsePositiveProbability)
    {
        if ($falsePositiveProbability <= 0 || $falsePositiveProbability >= 1) {
            throw new InvalidValue('False positive probability must be between 0 and 1');
        }

        $this->falsePositiveProbability = $falsePositiveProbability;
        $this->init();

        return $this;
    }

    /**
     * @param int $currentSetSize
     *
     * @return $this
     */
    public function setCurrentSetSize(int $currentSetSize)
    {
        $this->currentSetSize = $currentSetSize;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->currentSetSize = 0;
        $this->persister->reset();
    }

    /**
     * @inheritdoc
     */
    public function saveState(): Memento
    {
        $this->assertInit();
        $memento = new Memento();
        $memento->setHashClass(get_class($this->hash))
            ->addParam('setSize', $this->setSize)
            ->addParam('falsePositiveProbability', $this->falsePositiveProbability)
            ->addParam('currentSetSize', $this->currentSetSize);

        return $memento;
    }

    /**
     * @inheritdoc
     */
    public function restoreState(Memento $memento)
    {
        $this->checkIntegrity($memento);
        $this->setSize($memento->getParam('setSize'));
        $this->setFalsePositiveProbability($memento->getParam('falsePositiveProbability'));
        $this->setCurrentSetSize($memento->getParam('currentSetSize'));
        $this->bitSize = $this->getOptimalBitSize($this->setSize, $this->falsePositiveProbability);
        $this->hashCount = $this->getOptimalHashCount($this->setSize, $this->bitSize);
    }

    /**
     * @param Memento $memento
     */
    private function checkIntegrity(Memento $memento)
    {
        if ($memento->getHashClass() != get_class($this->hash)) {
            throw new CannotRestore('Memento object and filter should have same hash');
        }

        if ($memento->getParam('setSize') === null) {
            throw new CannotRestore('Memento object has not "setSize" parameter');
        }

        if ($memento->getParam('falsePositiveProbability') === null) {
            throw new CannotRestore('Memento object has not "falsePositiveProbability" parameter');
        }

        if ($memento->getParam('currentSetSize') === null) {
            throw new CannotRestore('Memento object has not "currentSetSize" parameter');
        }
    }

    /**
     * @return $this
     */
    protected function init()
    {
        if (isset($this->setSize) && isset($this->falsePositiveProbability)) {
            $this->bitSize = $this->getOptimalBitSize($this->setSize, $this->falsePositiveProbability);
            $this->hashCount = $this->getOptimalHashCount($this->setSize, $this->bitSize);
        }

        return $this;
    }

    protected function assertInit()
    {
        if (!isset($this->setSize) || !isset($this->falsePositiveProbability)) {
            throw new NotInitialized(static::class . ' should be initialized');
        }
    }

    /**
     * @param string $value
     * @param int $offset
     * @return array
     */
    protected function getBits(string $value, int $offset = 0): array
    {
        $bits = [];

        for ($i = 0; $i < $this->hashCount; $i++) {
            $bits[] = $this->hash($value, $i);
        }

        if ($offset === 0) {
            return $bits;
        } else {
            return array_map(
                function ($bit) use ($offset) {
                    return $bit + ($offset * $this->bitSize);
                },
                $bits
            );
        }
    }

    /**
     * m = ceil((n * log(p)) / log(1.0 / (pow(2.0, log(2.0)))));
     * m - Number of bits in the filter
     * n - Number of items in the filter
     * p - Probability of false positives, float between 0 and 1 or a number indicating 1-in-p
     *
     * @param int $setSize
     * @param float $falsePositiveProbability
     * @return int
     */
    protected function getOptimalBitSize(int $setSize, float $falsePositiveProbability = 0.001): int
    {
        return (int) round((($setSize * log($falsePositiveProbability)) / pow(log(2), 2)) * -1);
    }

    /**
     * k = round(log(2.0) * m / n);
     * k - Number of hash functions
     * m - Number of bits in the filter
     * n - Number of items in the filter
     *
     * @param int $setSize
     * @param int $bitSize
     * @return int
     */
    protected function getOptimalHashCount(int $setSize, int $bitSize): int
    {
        return (int) round(($bitSize / $setSize) * log(2));
    }

    /**
     * @param string $value
     * @param int $index
     *
     * @return int
     */
    private function hash(string $value, int $index)
    {
        return $this->hash->generate($value . $index) % $this->bitSize;
    }
}
