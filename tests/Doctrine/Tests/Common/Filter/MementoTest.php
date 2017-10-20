<?php

namespace Doctrine\Tests\Common\Filter\Persist;


use PHPUnit\Framework\TestCase;
use Doctrine\Common\Filter\Hash\Murmur;
use Doctrine\Common\Filter\Hash\Murmur as AnotherHash;
use Doctrine\Common\Filter\Memento;

class MementoTest extends TestCase
{

    /**
     * @test
     */
    public function setHashClass()
    {
        $memento = new Memento();
        $memento->setHashClass(Murmur::class);
        static::assertEquals(AnotherHash::class, $memento->getHashClass());
    }

    /**
     * @test
     */
    public function setParams()
    {
        $memento = new Memento();
        $memento->addParam('key', 1);

        self::assertEquals(1, $memento->getParam('key'));
        self::assertEquals(null, $memento->getParam('wrong_key'));
    }
}
