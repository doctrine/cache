<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;

/**
 * @group DCOM-101
 */
class FileCacheTest extends \Doctrine\Tests\DoctrineTestCase
{
    /**
     * @var \Doctrine\Common\Cache\FileCache
     */
    private $driver;

    protected function setUp()
    {
        $this->driver = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array(), '', false
        );
    }

    public function getProviderFileName()
    {
        return array(
            //The characters :\/<>"*?| are not valid in Windows filenames.
            array('key:1', 'key%3A1'),
            array('key\2', 'key%5C2'),
            array('key/3', 'key%2F3'),
            array('key<4', 'key%3C4'),
            array('key>5', 'key%3E5'),
            array('key"6', 'key%226'),
            array('key*7', 'key%2A7'),
            array('key?8', 'key%3F8'),
            array('key|9', 'key%7C9'),
            array('key[10]', 'key%5B10%5D'),
            array('keyä11', 'key%C3%A411'),
            array('../key12', '%2E%2E%2Fkey12'),
            array('.', '%2E'),
            array('..', '%2E%2E'),
            array('key-13', 'key-13'),
        );
    }

    /**
     * @dataProvider getProviderFileName
     */
    public function testInvalidFilename($key, $expected)
    {
        $cache  = $this->driver;
        $method = new \ReflectionMethod($cache, 'getFilename');

        $method->setAccessible(true);

        $value  = $method->invoke($cache, $key);
        $actual = pathinfo($value, PATHINFO_BASENAME);

        // On Windows, the hex percent-encoding has lowercase letters (rawurlencode vs bin2hex).
        $this->assertSame('\\' === DIRECTORY_SEPARATOR ? strtolower($expected) : $expected, $actual);
    }

    public function testFilenameCollision()
    {
        $data = array(
            'key:0',
            'key\0',
            'key/0',
            'key<0',
            'key>0',
            'key"0',
            'key*0',
            'key?0',
            'key|0',
            'key-0',
            'keyä0',
            'key'."\0".'0',
        );

        $paths  = array();
        $cache  = $this->driver;
        $method = new \ReflectionMethod($cache, 'getFilename');

        $method->setAccessible(true);

        foreach ($data as $key) {
            $path   = $method->invoke($cache, $key);

            $this->assertNotContains($path, $paths);

            $paths[] = $path;
        }
    }

    public function testFilenameShouldCreateThePathWithOneSubDirectory()
    {
        $cache          = $this->driver;
        $method         = new \ReflectionMethod($cache, 'getFilename');
        $key            = 'item-key';
        $expectedDir    = array(
            '84',
        );
        $expectedDir    = implode(DIRECTORY_SEPARATOR, $expectedDir);

        $method->setAccessible(true);

        $path       = $method->invoke($cache, $key);
        $dirname    = pathinfo($path, PATHINFO_DIRNAME);

        $this->assertEquals(DIRECTORY_SEPARATOR . $expectedDir, $dirname);
    }

    public function testFileExtensionCorrectlyEscaped()
    {
        $driver1 = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array(__DIR__, '.*')
        );
        $driver2 = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array(__DIR__, '.php')
        );

        $doGetStats = new \ReflectionMethod($driver1, 'doGetStats');

        $doGetStats->setAccessible(true);

        $stats1 = $doGetStats->invoke($driver1);
        $stats2 = $doGetStats->invoke($driver2);

        $this->assertSame(0, $stats1[Cache::STATS_MEMORY_USAGE]);
        $this->assertGreaterThan(0, $stats2[Cache::STATS_MEMORY_USAGE]);
    }

    /**
     * @group DCOM-266
     */
    public function testFileExtensionSlashCorrectlyEscaped()
    {
        $driver = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array(__DIR__ . '/../', DIRECTORY_SEPARATOR . basename(__FILE__))
        );

        $doGetStats = new \ReflectionMethod($driver, 'doGetStats');

        $doGetStats->setAccessible(true);

        $stats = $doGetStats->invoke($driver);

        $this->assertGreaterThan(0, $stats[Cache::STATS_MEMORY_USAGE]);
    }
}
