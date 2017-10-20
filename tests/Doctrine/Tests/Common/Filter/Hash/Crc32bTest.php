<?php

namespace Doctrine\Tests\Common\Filter\Hash;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Filter\Hash\Crc32b;

class Crc32bTest extends TestCase
{
    /**
     * @test
     */
    public function hash()
    {
        $hash = new Crc32b();
        $value = 'test value';
        $expected = 3973923115;

        static::assertEquals($expected, $hash->generate($value));
    }
}
