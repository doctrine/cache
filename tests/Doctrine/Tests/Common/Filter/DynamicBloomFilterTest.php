<?php

namespace Doctrine\Tests\Common\Filter\Hash;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Filter\DynamicBloomFilter;
use Doctrine\Common\Filter\Hash\Hash;
use Doctrine\Common\Filter\Persist\BitPersister;

class DynamicBloomFilterTest extends TestCase
{

    /**
     * @test
     */
    public function addToDynamicFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->willReturn(2);

        $persister->expects($this->at(0))
            ->method('setBulk')
            ->willReturn(1)
            ->with([2, 2, 2]); //calculated bits for hashes

        $persister->expects($this->at(3))
            ->method('setBulk')
            ->willReturn(1)
            ->with([16, 16, 16]); //calculated bits for hashes
        $persister->expects($this->at(6))
            ->method('setBulk')
            ->willReturn(1)
            ->with([30, 30, 30]); //calculated bits for hashes
        $persister->expects($this->at(9))
            ->method('setBulk')
            ->willReturn(1)
            ->with([44, 44, 44]); //calculated bits for hashes

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }
    }

    /**
     * @test
     */
    public function existsInFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->willReturn(2);

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $persister->expects($this->at(0))->method('getBulk')->willReturn([1, 0, 1])->with([2, 2 , 2]);
        $persister->expects($this->at(1))->method('getBulk')->willReturn([1, 1, 1])->with([16, 16 , 16]);

        static::assertTrue($filter->has('testString'));
    }

    /**
     * @test
     */
    public function suspendRestoreFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->willReturn(2);

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $persister->expects($this->at(0))->method('getBulk')->willReturn([1, 0, 1])->with([2, 2 , 2]);
        $persister->expects($this->at(1))->method('getBulk')->willReturn([1, 1, 1])->with([16, 16 , 16]);

        $memento = $filter->saveState();

        $restoredFilter = new DynamicBloomFilter($persister, $hash);
        $restoredFilter->restoreState($memento);

        static::assertTrue($restoredFilter->has('testString'));
    }

    /**
     * @test
     */
    public function doesNotExistsInFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->willReturn(2);

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $persister->expects($this->at(0))->method('getBulk')->willReturn([1, 0, 1])->with([2, 2 , 2]);
        $persister->expects($this->at(1))->method('getBulk')->willReturn([1, 1, 0])->with([16, 16, 16]);
        $persister->expects($this->at(2))->method('getBulk')->willReturn([1, 1, 0])->with([30, 30, 30]);
        $persister->expects($this->at(3))->method('getBulk')->willReturn([1, 1, 0])->with([44, 44, 44]);

        static::assertFalse($filter->has('testString'));
    }
}
