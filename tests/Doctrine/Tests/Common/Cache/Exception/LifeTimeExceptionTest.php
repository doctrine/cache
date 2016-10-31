<?php

namespace Doctrine\Tests\Common\Cache\Exception;

use Doctrine\Common\Cache\Exception\LifeTimeException;

class LifeTimeExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testFromNonIntegerLifetime()
    {
        $exception = LifeTimeException::fromNonIntegerLifetime('foo');

        $this->assertInstanceOf(LifeTimeException::class, $exception);
        $this->assertSame(
            'Lifetime should be an integer, string given',
            $exception->getMessage()
        );
    }

    public function testFromNegativeLifetime()
    {
        $exception = LifeTimeException::fromNegativeLifetime();

        $this->assertInstanceOf(LifeTimeException::class, $exception);
        $this->assertSame(
            'Cannot assign a negative value as LifeTime',
            $exception->getMessage()
        );
    }
}
