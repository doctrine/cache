<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FileCache;
use Doctrine\Tests\DoctrineTestCase;
use InvalidArgumentException;
use const DIRECTORY_SEPARATOR;
use const PATHINFO_DIRNAME;
use function basename;
use function bin2hex;
use function define;
use function defined;
use function floor;
use function hash;
use function mkdir;
use function pathinfo;
use function realpath;
use function sprintf;
use function str_repeat;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @group DCOM-101
 */
class FileCacheTest extends DoctrineTestCase
{
    /** @var FileCache */
    private $driver;

    protected function setUp() : void
    {
        $this->driver = $this
            ->getMockBuilder(FileCache::class)
            ->setMethods(['doFetch', 'doContains', 'doSave'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testFilenameShouldCreateThePathWithOneSubDirectory() : void
    {
        $cache       = $this->driver;
        $method      = new \ReflectionMethod($cache, 'getFilename');
        $key         = 'item-key';
        $expectedDir = '84';

        $method->setAccessible(true);

        $path    = $method->invoke($cache, $key);
        $dirname = pathinfo($path, PATHINFO_DIRNAME);

        self::assertEquals(DIRECTORY_SEPARATOR . $expectedDir, $dirname);
    }

    public function testFileExtensionCorrectlyEscaped() : void
    {
        $driver1 = $this
            ->getMockBuilder(FileCache::class)
            ->setMethods(['doFetch', 'doContains', 'doSave'])
            ->setConstructorArgs([__DIR__, '.*'])
            ->getMock();

        $driver2 = $this
            ->getMockBuilder(FileCache::class)
            ->setMethods(['doFetch', 'doContains', 'doSave'])
            ->setConstructorArgs([__DIR__, '.php'])
            ->getMock();

        $doGetStats = new \ReflectionMethod($driver1, 'doGetStats');

        $doGetStats->setAccessible(true);

        $stats1 = $doGetStats->invoke($driver1);
        $stats2 = $doGetStats->invoke($driver2);

        self::assertSame(0, $stats1[Cache::STATS_MEMORY_USAGE]);
        self::assertGreaterThan(0, $stats2[Cache::STATS_MEMORY_USAGE]);
    }

    /**
     * @group DCOM-266
     */
    public function testFileExtensionSlashCorrectlyEscaped() : void
    {
        $driver = $this
            ->getMockBuilder(FileCache::class)
            ->setMethods(['doFetch', 'doContains', 'doSave'])
            ->setConstructorArgs([__DIR__ . '/../', DIRECTORY_SEPARATOR . basename(__FILE__)])
            ->getMock();

        $doGetStats = new \ReflectionMethod($driver, 'doGetStats');

        $doGetStats->setAccessible(true);

        $stats = $doGetStats->invoke($driver);

        self::assertGreaterThan(0, $stats[Cache::STATS_MEMORY_USAGE]);
    }

    public function testNonIntUmaskThrowsInvalidArgumentException() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $this
            ->getMockBuilder(FileCache::class)
            ->setMethods(['doFetch', 'doContains', 'doSave'])
            ->setConstructorArgs(['', '', 'invalid'])
            ->getMock();
    }

    public function testGetDirectoryReturnsRealpathDirectoryString() : void
    {
        $directory = __DIR__ . '/../';

        $driver = $this
            ->getMockBuilder(FileCache::class)
            ->setMethods(['doFetch', 'doContains', 'doSave'])
            ->setConstructorArgs([$directory])
            ->getMock();

        $doGetDirectory = new \ReflectionMethod($driver, 'getDirectory');

        $actualDirectory   = $doGetDirectory->invoke($driver);
        $expectedDirectory = realpath($directory);

        self::assertEquals($expectedDirectory, $actualDirectory);
    }

    public function testGetExtensionReturnsExtensionString() : void
    {
        $directory = __DIR__ . '/../';
        $extension = DIRECTORY_SEPARATOR . basename(__FILE__);

        $driver = $this
            ->getMockBuilder(FileCache::class)
            ->setMethods(['doFetch', 'doContains', 'doSave'])
            ->setConstructorArgs([$directory, $extension])
            ->getMock();

        $doGetExtension = new \ReflectionMethod($driver, 'getExtension');

        $actualExtension = $doGetExtension->invoke($driver);

        self::assertEquals($extension, $actualExtension);
    }

    public const WIN_MAX_PATH_LEN = 258;

    public static function getBasePathForWindowsPathLengthTests(int $pathLength) : string
    {
        // Not using __DIR__ because it can get screwed up when xdebug debugger is attached.
        $basePath = realpath(sys_get_temp_dir()) . '/' . uniqid('doctrine-cache', true);

        /** @noinspection MkdirRaceConditionInspection */
        @mkdir($basePath);

        $basePath = realpath($basePath);

        // Test whether the desired path length is odd or even.
        $desiredPathLengthIsOdd = ($pathLength % 2) == 1;

        // If the cache key is not too long, the filecache codepath will add
        // a slash and bin2hex($key). The length of the added portion will be an odd number.
        // len(desired) = len(base path) + len(slash . bin2hex($key))
        //          odd = even           + odd
        //         even = odd            + odd
        $basePathLengthShouldBeOdd = ! $desiredPathLengthIsOdd;

        $basePathLengthIsOdd = (strlen($basePath) % 2) == 1;

        // If the base path needs to be odd or even where it is not, we add an odd number of
        // characters as a pad. In this case, we're adding '\aa' (or '/aa' depending on platform)
        // This is all to make it so that the key we're testing would result in
        // a path that is exactly the length we want to test IF the path length limit
        // were not in place in FileCache.
        if ($basePathLengthIsOdd != $basePathLengthShouldBeOdd) {
            $basePath .= DIRECTORY_SEPARATOR . 'aa';
        }

        return $basePath;
    }

    public static function getKeyAndPathFittingLength(int $length, string $basePath) : array
    {
        $baseDirLength   = strlen($basePath);
        $extensionLength = strlen('.doctrine.cache');
        $directoryLength = strlen(DIRECTORY_SEPARATOR . 'aa' . DIRECTORY_SEPARATOR);
        $keyLength       = $length - ($baseDirLength + $extensionLength + $directoryLength); // - 1 because of slash

        $key = str_repeat('a', floor($keyLength / 2));

        $keyHash = hash('sha256', $key);

        $keyPath = $basePath
            . DIRECTORY_SEPARATOR
            . substr($keyHash, 0, 2)
            . DIRECTORY_SEPARATOR
            . bin2hex($key)
            . '.doctrine.cache';

        $hashedKeyPath = $basePath
            . DIRECTORY_SEPARATOR
            . substr($keyHash, 0, 2)
            . DIRECTORY_SEPARATOR
            . '_' . $keyHash
            . '.doctrine.cache';

        return [$key, $keyPath, $hashedKeyPath];
    }

    public function getPathLengthsToTest() : array
    {
        // Windows officially supports 260 bytes including null terminator
        // 259 characters is too large due to PHP bug (https://bugs.php.net/bug.php?id=70943)
        // 260 characters is too large - null terminator is included in allowable length
        return [
            [257, false],
            [258, false],
            [259, true],
            [260, true],
        ];
    }

    /**
     * @dataProvider getPathLengthsToTest
     *
     * @covers \Doctrine\Common\Cache\FileCache::getFilename
     */
    public function testWindowsPathLengthLimitationsAreCorrectlyRespected(int $length, bool $pathShouldBeHashed) : void
    {
        if (! defined('PHP_WINDOWS_VERSION_BUILD')) {
            define('PHP_WINDOWS_VERSION_BUILD', 'Yes, this is the "usual suspect", with the usual limitations');
        }

        $basePath = self::getBasePathForWindowsPathLengthTests($length);

        $fileCache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\FileCache',
            [$basePath, '.doctrine.cache']
        );

        list($key, $keyPath, $hashedKeyPath) = self::getKeyAndPathFittingLength($length, $basePath);

        $getFileName = new \ReflectionMethod($fileCache, 'getFilename');

        $getFileName->setAccessible(true);

        self::assertEquals(
            $length,
            strlen($keyPath),
            sprintf('Path expected to be %d characters long is %d characters long', $length, strlen($keyPath))
        );

        if ($pathShouldBeHashed) {
            $keyPath = $hashedKeyPath;
        }

        if ($pathShouldBeHashed) {
            self::assertSame(
                $hashedKeyPath,
                $getFileName->invoke($fileCache, $key),
                'Keys should be hashed correctly if they are over the limit.'
            );
        } else {
            self::assertSame(
                $keyPath,
                $getFileName->invoke($fileCache, $key),
                'Keys below limit of the allowed length are used directly, unhashed'
            );
        }
    }
}
