<?php

namespace Doctrine\Tests\Common\Filter\Hash;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Filter\Hash\Murmur;

class MurmurTest extends TestCase
{
    /**
     * @test
     */
    public function hash()
    {
        $hash = new Murmur();
        $value = 'test value';
        $expected = 3804435892;

        static::assertEquals($expected, $hash->generate($value));
    }
}
