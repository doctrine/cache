<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\SQLite3Cache;
use SQLite3;

class SQLite3Test extends CacheTest
{
    /**
     * @var SQLite3
     */
    private $file, $sqlite;

    protected function setUp()
    {
        $this->file = tempnam(null, 'doctrine-cache-test-');
        unlink($this->file);
        $this->sqlite = new SQLite3($this->file);
    }

    protected function tearDown()
    {
        unlink($this->file);
    }

    public function testGetStats()
    {
        $this->assertNull($this->_getCacheDriver()->getStats());
    }

    protected function _getCacheDriver()
    {
        return new SQLite3Cache($this->sqlite, 'test_table');
    }
}
