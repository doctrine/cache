<?php

namespace Doctrine\Tests\Common\Filter\Persist;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Filter\Persist\CountRedis;

class CountRedisTest extends TestCase
{
    /**
     * @test
     */
    public function incrementBit()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(1)
            ->with($key, 100, 1);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, $key);
        self::assertEquals(1, $persister->incrementBit(100));
    }

    /**
     * @test
     */
    public function decrementBit()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(0)
            ->with($key, 100, -1);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, $key);
        self::assertEquals(0, $persister->decrementBit(100));
    }

    /**
     * @test
     */
    public function getBit()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('hGet')
            ->willReturn(10)
            ->with($key, 100);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, $key);
        self::assertEquals(10, $persister->get(100));
    }


    /**
     * @test
     * @expectedException  \RuntimeException
     */
    public function negativeDecrement()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(-1)
            ->with($key, 100, -1);
        $redisMock->expects($this->once())
            ->method('hSet')
            ->willReturn(0)
            ->with($key, 100, 0);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, $key);
        self::assertEquals(0, $persister->decrementBit(100));
    }

    /**
     * @test
     */
    public function reset()
    {
        $key = 'bloom';
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('del')
            ->willReturn(1)
            ->with($key);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, $key);
        $persister->reset(100);
    }
}
