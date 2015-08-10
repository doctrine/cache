<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Filesystem\Filesystem;

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

    public function tearDown()
    {
        $filesystem = new Filesystem;
        $filesystem->remove($this->getCacheDir());
    }

    public function getProviderFileName()
    {
         return array(
            //The characters :\/<>"*?| are not valid in Windows filenames.
            array('key:1', 'key-1'),
            array('key\2', 'key-2'),
            array('key/3', 'key-3'),
            array('key<4', 'key-4'),
            array('key>5', 'key-5'),
            array('key"6', 'key-6'),
            array('key*7', 'key-7'),
            array('key?8', 'key-8'),
            array('key|9', 'key-9'),
            array('key[10]', 'key[10]'),
            array('keyä11', 'key--11'),
            array('../key12', '---key12'),
            array('key-13', 'key__13'),
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
        $actual = pathinfo($value, PATHINFO_FILENAME);

        $this->assertEquals($expected, $actual);
    }

    public function testFilenameCollision()
    {
        $data = array(
            'key:0' => 'key-0',
            'key\0' => 'key-0',
            'key/0' => 'key-0',
            'key<0' => 'key-0',
            'key>0' => 'key-0',
            'key"0' => 'key-0',
            'key*0' => 'key-0',
            'key?0' => 'key-0',
            'key|0' => 'key-0',
            'key-0' => 'key__0',
            'keyä0' => 'key--0',
        );

        $paths  = array();
        $cache  = $this->driver;
        $method = new \ReflectionMethod($cache, 'getFilename');

        $method->setAccessible(true);

        foreach ($data as $key => $expected) {
            $path   = $method->invoke($cache, $key);
            $actual = pathinfo($path, PATHINFO_FILENAME);

            $this->assertNotContains($path, $paths);
            $this->assertEquals($expected, $actual);

            $paths[] = $path;
        }
    }

    public function testFilenameShouldCreateThePathWithFourSubDirectories()
    {
        $cache          = $this->driver;
        $method         = new \ReflectionMethod($cache, 'getFilename');
        $key            = 'item-key';
        $expectedDir    = array(
            '84', 'e0', 'e2', 'e8', '93', 'fe', 'bb', '73', '7a', '0f', 'ee',
            '0c', '89', 'd5', '3f', '4b', 'b7', 'fc', 'b4', '4c', '57', 'cd',
            'f3', 'd3', '2c', 'e7', '36', '3f', '5d', '59', '77', '60'
        );
        $expectedDir    = implode(DIRECTORY_SEPARATOR, $expectedDir);

        $method->setAccessible(true);

        $path       = $method->invoke($cache, $key);
        $filename   = pathinfo($path, PATHINFO_FILENAME);
        $dirname    = pathinfo($path, PATHINFO_DIRNAME);

        $this->assertEquals('item__key', $filename);
        $this->assertEquals(DIRECTORY_SEPARATOR . $expectedDir, $dirname);
        $this->assertEquals(DIRECTORY_SEPARATOR . $expectedDir . DIRECTORY_SEPARATOR . 'item__key', $path);
    }

    public function testFileExtensionCorrectlyEscaped()
    {
        $driver1 = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array($this->getCacheDir(), '.*')
        );
        $driver2 = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array($this->getCacheDir(), '.php')
        );

        file_put_contents(
            $driver1->getDirectory() . DIRECTORY_SEPARATOR . 'a.php',
            str_repeat('x', 1024)
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
            array($this->getCacheDir(), '/a.php')
        );

        file_put_contents(
            $driver->getDirectory() . DIRECTORY_SEPARATOR . 'a.php',
            str_repeat('x', 1024)
        );

        $doGetStats = new \ReflectionMethod($driver, 'doGetStats');

        $doGetStats->setAccessible(true);

        $stats = $doGetStats->invoke($driver);

        $this->assertGreaterThan(0, $stats[Cache::STATS_MEMORY_USAGE]);
    }

    public function testSeparateCachesAreCreatedForEachUser()
    {
        if (!function_exists('posix_geteuid')) {
            $this->markTestSkipped(
                'User-specific caches are only used on POSIX systems.'
            );
        }

        $driver = $this->getMock(
            'Doctrine\Common\Cache\FileCache',
            array('doFetch', 'doContains', 'doSave'),
            array($this->getCacheDir(), '.php')
        );
        $myUserData = posix_getpwuid(posix_geteuid());
        $myUserName = $myUserData['name'];

        $this->assertContains($myUserName, $driver->getDirectory());
        $this->assertSame(posix_geteuid(), fileowner($driver->getDirectory()));
    }

    private function getCacheDir()
    {
        static $dir;

        if (empty($dir)) {
            $dir = implode(DIRECTORY_SEPARATOR, array(
                dirname(dirname(dirname(dirname(__DIR__)))),
                'fixtures',
                'cache',
                md5(gethostname())
            ));
        }

        return $dir;
    }
}
