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
        $this->sqlite = null;  // DB must be closed before
        unlink($this->file);
    }

    public function testGetStats()
    {
        $this->assertNull($this->_getCacheDriver()->getStats());
    }

    public function testFetchSingle()
    {
        $id = uniqid('sqlite3_id_');

        $data = $this->_getCacheDriver();

        $this->_getCacheDriver()->save($id, $data, 30);

        try {
            $actual = $this->_getCacheDriver()->fetch($id);

        } catch (\PHPUnit_Framework_Error $e) {
            $this->fail('Unexpected exception has been raised. ' . $e->getMessage());
        }

        $this->assertEquals(
            $data,
            $actual,
            'data saved and retrieved does not match.'
        );
    }

    protected function _getCacheDriver()
    {
        return new SQLite3Cache($this->sqlite, 'test_table');
    }
}
