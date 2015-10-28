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

    public function testNonIntUmaskThrowsInvalidArgumentException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array(),
            array('', '', 'invalid')
        );
    }

    public function testGetDirectoryReturnsRealpathDirectoryString()
    {
        $directory = __DIR__ . '/../';
        $driver = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array($directory)
        );

        $doGetDirectory = new \ReflectionMethod($driver, 'getDirectory');

        $actualDirectory = $doGetDirectory->invoke($driver);
        $expectedDirectory = realpath($directory);

        $this->assertEquals($expectedDirectory, $actualDirectory);
    }

    public function testGetExtensionReturnsExtensionString()
    {
        $directory = __DIR__ . '/../';
        $extension = DIRECTORY_SEPARATOR . basename(__FILE__);
        $driver = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array($directory, $extension)
        );

        $doGetExtension = new \ReflectionMethod($driver, 'getExtension');

        $actualExtension = $doGetExtension->invoke($driver);

        $this->assertEquals($extension, $actualExtension);
    }
}
