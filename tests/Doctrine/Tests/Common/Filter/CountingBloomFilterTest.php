<?php

namespace Doctrine\Tests\Common\Filter\Hash;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Filter\CountingBloomFilter;
use Doctrine\Common\Filter\Hash\Hash;
use Doctrine\Common\Filter\Persist\BitPersister;
use Doctrine\Common\Filter\Persist\CountPersister;

class CountingBloomFilterTest extends TestCase
{

    /**
     * @test
     */
    public function addToFilter()
    {
        $bitPersister = $this->getMockBuilder(BitPersister::class)->getMock();

        $bitPersister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes

        $countPersister = $this->getMockBuilder(CountPersister::class)->getMock();
        $countPersister->expects($this->exactly(3))
            ->method('incrementBit')
            ->willReturn(1)
            ->will($this->onConsecutiveCalls(42, 1000, 10048));

        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->exactly(3))
            ->method('generate')
            ->will($this->onConsecutiveCalls(42, 1000, 10048));



        $filter = new CountingBloomFilter($bitPersister, $countPersister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->add('testString');
    }

    /**
     * @test
     */
    public function deleteFromFilter()
    {
        $bitPersister = $this->getMockBuilder(BitPersister::class)->getMock();

        $bitPersister->expects($this->once())
            ->method('unsetBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes

        $countPersister = $this->getMockBuilder(CountPersister::class)->getMock();
        $countPersister->expects($this->exactly(3))
            ->method('decrementBit')
            ->willReturn(1)
            ->will($this->onConsecutiveCalls(42, 1000, 232));

        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->exactly(3))
            ->method('generate')
            ->will($this->onConsecutiveCalls(42, 1000, 10048));



        $filter = new CountingBloomFilter($bitPersister, $countPersister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->delete('testString');
    }

    /**
     * @test
     */
    public function deleteBulkFilter()
    {
        $bitPersister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->exactly(9))
            ->method('generate')
            ->will($this->onConsecutiveCalls(42, 43, 44, 1, 2, 3, 10001, 10002, 10003));

        $bitPersister->expects($this->once())
            ->method('unsetBulk')
            ->willReturn(1)
            ->with([42, 43, 44, 1, 2, 3, 185, 186, 187]); //calculated bits for hashes

        $countPersister = $this->getMockBuilder(CountPersister::class)->getMock();
        $countPersister->expects($this->exactly(9))
            ->method('decrementBit')
            ->willReturn(1);


        $filter = new CountingBloomFilter($bitPersister, $countPersister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->deleteBulk(
            ['test String 1',
                'test String 2',
                'test String 3',
            ]
        );
    }

    /**
     * @test
     */
    public function addBulkFilter()
    {
        $bitPersister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->exactly(9))
            ->method('generate')
            ->will( $this->onConsecutiveCalls(42, 43, 44, 1, 2, 3, 10001, 10002, 10003));

        $bitPersister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 43, 44, 1, 2, 3, 185, 186, 187]); //calculated bits for hashes

        $countPersister = $this->getMockBuilder(CountPersister::class)->getMock();
        $countPersister->expects($this->once())
            ->method('incrementBulk')
            ->willReturn([])
            ->with([42, 43, 44, 1, 2, 3, 185, 186, 187]);

        $filter = new CountingBloomFilter($bitPersister, $countPersister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->addBulk(
            ['test String 1',
            'test String 2',
            'test String 3',
            ]
        );
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
            ->will( $this->onConsecutiveCalls(42, 1000, 10001, 42, 1000, 10001));

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 185]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 1, 1])
            ->with([42, 1000, 185]); //calculated bits for hashes

        $countPersister = $this->getMockBuilder(CountPersister::class)->getMock();

        $filterForSet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('testString');

        $filterForGet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForGet->setSize(1024)->setFalsePositiveProbability(0.1);
        static::assertTrue($filterForGet->has('testString'));
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
            ->will( $this->onConsecutiveCalls(42, 1000, 10001, 42, 1000, 10001));

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 185]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 1, 1])
            ->with([42, 1000, 185]); //calculated bits for hashes

        $countPersister = $this->getMockBuilder(CountPersister::class)->getMock();

        $filterForSet = new CountingBloomFilter($persister, $countPersister, $hash);

        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('testString');
        $memento = $filterForSet->saveState();

        $filterForGet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForGet->restoreState($memento);
        static::assertTrue($filterForGet->has('testString'));
    }

    /**
     * @test
     */
    public function DoesNotExistInFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes

        $countPersister = $this->getMockBuilder(CountPersister::class)->getMock();
        $countPersister->expects($this->exactly(3))
            ->method('incrementBit')
            ->willReturn(1)
            ->will($this->onConsecutiveCalls(42, 1000, 232));

        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 0, 1])
            ->with([43, 1001, 233]); //calculated bits for hashes

        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->exactly(6))
            ->method('generate')
            ->will( $this->onConsecutiveCalls(42, 1000, 10048, 43, 1001, 10049));

        $filterForSet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('test String');

        $filterForGet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForGet->setSize(1024)->setFalsePositiveProbability(0.1);
        static::assertFalse($filterForGet->has('Not Existing test String'));
    }
}
