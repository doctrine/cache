<?php

namespace Doctrine\Tests\Common\Filter\Persist;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Filter\Persist\BitRedis;

class BitRedisTest extends TestCase
{

    /**
     * @test
     */
    public function setBit()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('setBit')
            ->willReturn(1)
            ->with($key, 100, 1);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->set(100);
    }

    /**
     * @test
     */
    public function unsetBit()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('setBit')
            ->willReturn(1)
            ->with($key, 100, 0);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->unset(100);
    }

    /**
     * @test
     */
    public function getBit()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('getBit')
            ->willReturn(0)
            ->with($key, 100);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        static::assertEquals(0, $persister->get(100));
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function setNegativeBit()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->set(-1);
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function getNegativeBit()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->set(-1);
        $persister->set(-1);

    }

    /**
     * @test
     * @expectedException \TypeError
     */
    public function getWrongBitValue()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->set('test');
    }


    /**
     * @test
     */
    public function setBits()
    {
        $bits = [2, 16, 250];
        $key = 'bloom';
        $pipeMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('setBit')
            ->withConsecutive(
                [$key, $bits[0], 1],
                [$key, $bits[1], 1],
                [$key, $bits[2], 1]
            )
            ->willReturn(1);

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn(1);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->setBulk($bits);
    }

    /**
     * @test
     */
    public function unsetBits()
    {
        $key = 'bloom';
        $bits = [2, 16, 250];
        $pipeMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('setBit')
            ->withConsecutive(
                [$key, $bits[0], 0],
                [$key, $bits[1], 0],
                [$key, $bits[2], 0]
            )
            ->willReturn(1);

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn(1);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->unsetBulk($bits);
    }

    /**
     * @test
     */
    public function reset()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('del');

        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->reset();
    }

    /**
     * @test
     */
    public function getBits()
    {
        $key = 'bloom';
        $bits = [2, 16, 250];
        $pipeMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('getBit')
            ->withConsecutive(
                [$key, $bits[0]],
                [$key, $bits[1]],
                [$key, $bits[2]]
            )
            ->willReturn([1,1,1]);

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn([1]);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, $key);
        $persister->getBulk($bits);
    }
}
