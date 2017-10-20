<?php

namespace Doctrine\Tests\Common\Filter\Persist;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Filter\Persist\BitString;

class BitStringTest extends TestCase
{
    /**
     * @test
     */
    public function createWithDefaultSize()
    {
        $persister = new BitString();

        $class = new \ReflectionClass(BitString::class);
        $propertyBytes = $class->getProperty("bytes");
        $propertySize = $class->getProperty("size");
        $propertyBytes->setAccessible(true);
        $propertySize->setAccessible(true);

        static::assertEquals(BitString::DEFAULT_BYTE_SIZE, $propertySize->getValue($persister));
        static::assertEquals(BitString::DEFAULT_BYTE_SIZE, strlen($propertyBytes->getValue($persister)));
    }

    /**
     * @test
     */
    public function setBit()
    {
        $persister = new BitString();
        $persister->set(100);
        static::assertEquals(1, $persister->get(100));

        $allNotSetBitsAreOff = true;

        for ($i = 0; $i < BitString::DEFAULT_BYTE_SIZE * 8; $i++) {
            if ($i == 100) {
                continue;
            }
            $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
        }

        static::assertTrue($allNotSetBitsAreOff);

    }

    /**
     * @test
     */
    public function unsetBit()
    {
        $persister = new BitString();
        $persister->set(100);
        static::assertEquals(1, $persister->get(100));
        $persister->unset(99);
        static::assertEquals(1, $persister->get(100));
        $persister->unset(100);
        static::assertEquals(0, $persister->get(100));
    }

    /**
     * @test
     */
    public function bitIsNotSet()
    {
        $persister = new BitString();
        $allNotSetBitsAreOff = true;

        for ($i = 0; $i < BitString::DEFAULT_BYTE_SIZE * 8; $i++) {
            $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
        }

        static::assertTrue($allNotSetBitsAreOff);
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function setNegativeBit()
    {
        $persister = new BitString();
        $persister->set(-1);
    }

    /**
     * @test
     * @expectedException \TypeError
     */
    public function getWrongBitValue()
    {
        $persister = new BitString();
        $persister->set('test');
    }

    /**
     * @test
     */
    public function setBits()
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);

        foreach ($bits as $bit) {
            static::assertEquals(1, $persister->get($bit));
        }
    }

    /**
     * @test
     */
    public function unsetBits()
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);
        $persister->unsetBulk($bits);

        foreach ($bits as $bit) {
            static::assertEquals(0, $persister->get($bit));
        }
    }

    /**
     * @test
     */
    public function reset()
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);
        $persister->reset();

        foreach ($bits as $bit) {
            static::assertEquals(0, $persister->get($bit));
        }
    }

    /**
     * @test
     */
    public function getBits()
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);

        static::assertEquals([1, 1, 1, 1, 0], $persister->getBulk(array_merge($bits, [512])));
    }

    /**
     * @test
     */
    public function increaseSize()
    {
        $persister = new BitString();

        $class = new \ReflectionClass(BitString::class);
        $propertyBytes = $class->getProperty("bytes");
        $propertySize = $class->getProperty("size");
        $propertyBytes->setAccessible(true);
        $propertySize->setAccessible(true);

        $bit = BitString::DEFAULT_BYTE_SIZE * 8 * 3 + 2;
        $increasedSize =  BitString::DEFAULT_BYTE_SIZE * 3 + BitString::DEFAULT_BYTE_SIZE;

        $persister->set($bit);
        $allNotSetBitsAreOff = true;
         for ($i = 0; $i < $increasedSize; $i++) {
             if ($i == $bit) {
                 continue;
             }
             $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
         }


        static::assertTrue($allNotSetBitsAreOff);
        static::assertEquals($increasedSize, $propertySize->getValue($persister));
        static::assertEquals($increasedSize, strlen($propertyBytes->getValue($persister)));
    }
}
