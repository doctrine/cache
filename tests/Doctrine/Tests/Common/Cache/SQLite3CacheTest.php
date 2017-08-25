<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\SQLite3Cache;
use SQLite3;

/**
 * @requires extension sqlite3 >= 3
 */
class SQLite3CacheTest extends CacheTest
{
    private $file;
    private $sqlite;

    protected function setUp() : void
    {
        $this->file = tempnam(null, 'doctrine-cache-test-');
        unlink($this->file);
        $this->sqlite = new SQLite3($this->file);
    }

    protected function tearDown() : void
    {
        $this->sqlite = null;  // DB must be closed before
        unlink($this->file);
    }

    public function testGetStats() : void
    {
        self::assertNull($this->_getCacheDriver()->getStats());
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCacheDriver() : CacheProvider
    {
        return new SQLite3Cache($this->sqlite, 'test_table');
    }
}
