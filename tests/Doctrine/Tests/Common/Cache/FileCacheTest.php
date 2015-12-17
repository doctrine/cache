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
            array('doFetch', 'doContains', 'doSave'),
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

    /**
     * @runInSeparateProcess
     *
     * @covers \Doctrine\Common\Cache\FileCache::getFilename
     */
    public function testWindowsPathLengthLimitationsAreCorrectlyRespected()
    {
        if (! defined('PHP_WINDOWS_VERSION_BUILD')) {
            define('PHP_WINDOWS_VERSION_BUILD', 'Yes, this is the "usual suspect", with the usual limitations');
        }

        // Not using __DIR__ because it can get screwed up when xdebug debugger is attached.
        $basePath = realpath(sys_get_temp_dir());

        // If the base path length is even, pad it with '/aa' so it's odd.
        // That way we can test a path of length 260
        // 260 characters is too large - null terminator is included in allowable length
        if (!(strlen($basePath) % 1)) {
            $basePath .= DIRECTORY_SEPARATOR . "aa";
        }

        $fileCache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\FileCache',
            array($basePath, '.doctrine.cache')
        );

        $baseDirLength        = strlen($basePath);
        $extensionLength      = strlen('.doctrine.cache');
        $windowsPathMaxLength = 259; // 260 bytes including null terminator
        $maxKeyLength         = $windowsPathMaxLength - ($baseDirLength + $extensionLength);

        self::assertSame('61', bin2hex('a'), '(added just for clarity and system integrity check)');

        $tooLongKey = str_repeat('a', ($maxKeyLength / 2) - 1); // note: 1 char because reasons, ok?
        $fittingKey = str_repeat('a', ($maxKeyLength / 2) - 2); // note: 2 chars due to path separator added as well

        $tooLongKeyHash = hash('sha256', $tooLongKey);
        $fittingKeyHash = hash('sha256', $fittingKey);

        $getFileName = new \ReflectionMethod($fileCache, 'getFilename');

        $getFileName->setAccessible(true);

        $this->assertEquals(
            $windowsPathMaxLength + 1,
            strlen($basePath
                . DIRECTORY_SEPARATOR
                . substr($tooLongKeyHash, 0, 2)
                . DIRECTORY_SEPARATOR
                . bin2hex($tooLongKey)
                . '.doctrine.cache'),
            sprintf('Key expected to be too long is %d characters long', $windowsPathMaxLength + 1)
        );

        $this->assertSame(
            $basePath . DIRECTORY_SEPARATOR . substr($tooLongKeyHash, 0, 2) . DIRECTORY_SEPARATOR . '_' . $tooLongKeyHash . '.doctrine.cache',
            $getFileName->invoke($fileCache, $tooLongKey),
            'Keys over the limit of the allowed length are hashed correctly'
        );

        $this->assertLessThan(
            $windowsPathMaxLength,
            strlen($basePath
                . DIRECTORY_SEPARATOR
                . substr($fittingKeyHash, 0, 2)
                . DIRECTORY_SEPARATOR
                . bin2hex($fittingKey)
                . '.doctrine.cache'),
            sprintf(
                'Key expected to fit the length limit(%d) is less than %d characters long',
                $windowsPathMaxLength,
                $windowsPathMaxLength
            )
        );

        $this->assertSame(
            $basePath . DIRECTORY_SEPARATOR . substr($fittingKeyHash, 0, 2) . DIRECTORY_SEPARATOR . bin2hex($fittingKey) . '.doctrine.cache',
            $getFileName->invoke($fileCache, $fittingKey),
            'Keys below limit of the allowed length are used directly, unhashed'
        );
    }
}
