<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\SQLite3Cache;
use SQLite3;

use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * @requires extension sqlite3 >= 3
 */
class SQLite3CacheTest extends CacheTest
{
    /** @var string */
    private $file;

    /** @var SQLite3 */
    private $sqlite;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'doctrine-cache-test-');
        unlink($this->file);
        $this->sqlite = new SQLite3($this->file);
    }

    protected function tearDown(): void
    {
        $this->sqlite = null;  // DB must be closed before
        unlink($this->file);
    }

    public function testGetStats(): void
    {
        self::assertNull($this->getCacheDriver()->getStats());
    }

    protected function getCacheDriver(): CacheProvider
    {
        return new SQLite3Cache($this->sqlite, 'test_table');
    }
}
