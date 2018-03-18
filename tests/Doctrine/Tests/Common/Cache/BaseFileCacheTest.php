<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\FileCache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use const DIRECTORY_SEPARATOR;
use function bin2hex;
use function file_exists;
use function floor;
use function get_class;
use function hash;
use function is_dir;
use function rmdir;
use function str_repeat;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

abstract class BaseFileCacheTest extends CacheTest
{
    protected $directory;

    protected function setUp() : void
    {
        do {
            $this->directory = sys_get_temp_dir() . '/doctrine_cache_' . uniqid();
        } while (file_exists($this->directory));
    }

    protected function tearDown() : void
    {
        if (! is_dir($this->directory)) {
            return;
        }

        $iterator = new RecursiveDirectoryIterator($this->directory);

        foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isFile()) {
                @unlink($file->getRealPath());
            } elseif ($file->isDir()) {
                @rmdir($file->getRealPath());
            }
        }

        @rmdir($this->directory);
    }

    public function testFlushAllRemovesBalancingDirectories() : void
    {
        $cache = $this->_getCacheDriver();

        self::assertTrue($cache->save('key1', 1));
        self::assertTrue($cache->save('key2', 2));
        self::assertTrue($cache->flushAll());

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        self::assertCount(0, $iterator);
    }

    protected function isSharedStorage() : bool
    {
        return false;
    }

    public function getPathLengthsToTest() : array
    {
        // Windows officially supports 260 bytes including null terminator
        // 258 bytes available to use due to php bug #70943
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

    private static function getBasePathForWindowsPathLengthTests(int $pathLength) : string
    {
        return FileCacheTest::getBasePathForWindowsPathLengthTests($pathLength);
    }

    private static function getKeyAndPathFittingLength(int $length, string $basePath) : array
    {
        $baseDirLength             = strlen($basePath);
        $extensionLength           = strlen('.doctrine.cache');
        $directoryLength           = strlen(DIRECTORY_SEPARATOR . 'aa' . DIRECTORY_SEPARATOR);
        $namespaceAndBracketLength = strlen(bin2hex('[][1]'));
        $keyLength                 = $length
            - ($baseDirLength
                + $extensionLength
                + $directoryLength
                + $namespaceAndBracketLength);

        $key           = str_repeat('a', floor($keyLength / 2));
        $namespacedKey = '[' . $key . '][1]';

        $keyHash = hash('sha256', $namespacedKey);

        $keyPath = $basePath
            . DIRECTORY_SEPARATOR
            . substr($keyHash, 0, 2)
            . DIRECTORY_SEPARATOR
            . bin2hex($namespacedKey)
            . '.doctrine.cache';

        $hashedKeyPath = $basePath
            . DIRECTORY_SEPARATOR
            . substr($keyHash, 0, 2)
            . DIRECTORY_SEPARATOR
            . '_' . $keyHash
            . '.doctrine.cache';

        return [$key, $keyPath, $hashedKeyPath];
    }

    /**
     * @dataProvider getPathLengthsToTest
     */
    public function testWindowsPathLengthLimitIsCorrectlyHandled(int $length, bool $pathShouldBeHashed) : void
    {
        $this->directory = self::getBasePathForWindowsPathLengthTests($length);

        list($key, $keyPath, $hashedKeyPath) = self::getKeyAndPathFittingLength($length, $this->directory);

        self::assertEquals($length, strlen($keyPath), 'Unhashed path should be of correct length.');

        $cacheClass = get_class($this->_getCacheDriver());
        /* @var $cache \Doctrine\Common\Cache\FileCache */
        $cache = new $cacheClass($this->directory, '.doctrine.cache');

        // Trick it into thinking this is windows.
        $reflClass = new \ReflectionClass(FileCache::class);
        $reflProp  = $reflClass->getProperty('isRunningOnWindows');
        $reflProp->setAccessible(true);
        $reflProp->setValue($cache, true);
        $reflProp->setAccessible(false);

        $value = uniqid('value', true);

        $cache->save($key, $value);
        self::assertEquals($value, $cache->fetch($key));

        if ($pathShouldBeHashed) {
            self::assertFileExists($hashedKeyPath, 'Path generated for key should be hashed.');
            unlink($hashedKeyPath);
        } else {
            self::assertFileExists($keyPath, 'Path generated for key should not be hashed.');
            unlink($keyPath);
        }
    }
}
