<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcacheCache;
use Memcache;

use const PHP_VERSION_ID;

/**
 * @requires extension memcache
 */
class MemcacheCacheTest extends CacheTest
{
    /** @var Memcache */
    private $memcache;

    protected function setUp(): void
    {
        $this->memcache = new Memcache();

        if (@$this->memcache->connect('localhost', 11211) !== false) {
            return;
        }

        unset($this->memcache);
        $this->markTestSkipped('Cannot connect to Memcache.');
    }

    protected function tearDown(): void
    {
        if (! ($this->memcache instanceof Memcache)) {
            return;
        }

        $this->memcache->flush();
    }

    /**
     * {@inheritdoc}
     *
     * Memcache does not support " " and null byte as key so we remove them from the tests.
     */
    public function provideCacheIds(): array
    {
        $ids = parent::provideCacheIds();
        unset($ids[21], $ids[22]);

        return $ids;
    }

    public function testGetMemcacheReturnsInstanceOfMemcache(): void
    {
        self::assertInstanceOf('Memcache', $this->getCacheDriver()->getMemcache());
    }

    protected function getCacheDriver(): CacheProvider
    {
        $driver = new MemcacheCache();
        $driver->setMemcache($this->memcache);

        return $driver;
    }

    /**
     * @param mixed $value
     *
     * @dataProvider provideDataToCache
     */
    public function testSetContainsFetchDelete($value): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('this is probably a bug that needs to be fixed');
        }

        parent::testSetContainsFetchDelete($value);
    }

    /**
     * @param mixed $value
     *
     * @dataProvider provideDataToCache
     */
    public function testUpdateExistingEntry($value): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('this is probably a bug that needs to be fixed');
        }

        parent::testSetContainsFetchDelete($value);
    }
}
