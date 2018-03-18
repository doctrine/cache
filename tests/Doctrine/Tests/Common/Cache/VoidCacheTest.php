<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\VoidCache;
use Doctrine\Tests\DoctrineTestCase;

/**
 * @covers \Doctrine\Common\Cache\VoidCache
 */
class VoidCacheTest extends DoctrineTestCase
{
    public function testShouldAlwaysReturnFalseOnContains() : void
    {
        $cache = new VoidCache();

        self::assertFalse($cache->contains('foo'));
        self::assertFalse($cache->contains('bar'));
    }

    public function testShouldAlwaysReturnFalseOnFetch() : void
    {
        $cache = new VoidCache();

        self::assertFalse($cache->fetch('foo'));
        self::assertFalse($cache->fetch('bar'));
    }

    public function testShouldAlwaysReturnTrueOnSaveButNotStoreAnything() : void
    {
        $cache = new VoidCache();

        self::assertTrue($cache->save('foo', 'fooVal'));

        self::assertFalse($cache->contains('foo'));
        self::assertFalse($cache->fetch('foo'));
    }

    public function testShouldAlwaysReturnTrueOnDelete() : void
    {
        $cache = new VoidCache();

        self::assertTrue($cache->delete('foo'));
    }

    public function testShouldAlwaysReturnNullOnGetStatus() : void
    {
        $cache = new VoidCache();

        self::assertNull($cache->getStats());
    }

    public function testShouldAlwaysReturnTrueOnFlush() : void
    {
        $cache = new VoidCache();

        self::assertTrue($cache->flushAll());
    }
}
