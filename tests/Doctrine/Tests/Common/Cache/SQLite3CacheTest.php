<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\SQLite3Cache;
use SQLite3;

/**
 * @requires extension sqlite3
 */
class SQLite3Test extends CacheTest
{
    private $file;
    private $sqlite;

    protected function setUp()
    {
        $this->file = tempnam(null, 'doctrine-cache-test-');
        unlink($this->file);
        $this->sqlite = new SQLite3($this->file);
    }

    protected function tearDown()
    {
        $this->sqlite = null;  // DB must be closed before
        unlink($this->file);
    }

    public function testGetStats()
    {
        $this->assertNull($this->_getCacheDriver()->getStats());
    }

    public function testFetchSingle()
    {
        $id   = uniqid('sqlite3_id_');
        $data = "\0"; // produces null bytes in serialized format

        $this->_getCacheDriver()->save($id, $data, 30);

        $this->assertEquals($data, $this->_getCacheDriver()->fetch($id));
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCacheDriver()
    {
        return new SQLite3Cache($this->sqlite, 'test_table');
    }
}
