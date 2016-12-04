<?php

namespace Doctrine\Tests\Common\Cache;

class CacheProviderTest extends \Doctrine\Tests\DoctrineTestCase
{
    public function testFetchMultiWillFilterNonRequestedKeys()
    {
        /* @var $cache \Doctrine\Common\Cache\CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
        $cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            [],
            '',
            true,
            true,
            true,
            ['doFetchMultiple']
        );

        $cache
            ->expects($this->once())
            ->method('doFetchMultiple')
            ->will($this->returnValue([
                '[foo][1]' => 'bar',
                '[bar][1]' => 'baz',
                '[baz][1]' => 'tab',
            ]));

        $this->assertEquals(
            ['foo' => 'bar', 'bar' => 'baz'],
            $cache->fetchMultiple(['foo', 'bar'])
        );
    }

    public function testFailedDeleteAllDoesNotChangeNamespaceVersion()
    {
        /* @var $cache \Doctrine\Common\Cache\CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
        $cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            [],
            '',
            true,
            true,
            true,
            ['doFetch', 'doSave', 'doContains']
        );

        $cache
            ->expects($this->once())
            ->method('doFetch')
            ->with('DoctrineNamespaceCacheKey[]')
            ->will($this->returnValue(false));

        // doSave is only called once from deleteAll as we do not need to persist the default version in getNamespaceVersion()
        $cache
            ->expects($this->once())
            ->method('doSave')
            ->with('DoctrineNamespaceCacheKey[]')
            ->will($this->returnValue(false));

        // After a failed deleteAll() the local namespace version is not increased (still 1). Otherwise all data written afterwards
        // would be lost outside the current instance.
        $cache
            ->expects($this->once())
            ->method('doContains')
            ->with('[key][1]')
            ->will($this->returnValue(true));

        $this->assertFalse($cache->deleteAll(), 'deleteAll() returns false when saving the namespace version fails');
        $cache->contains('key');
    }

    public function testSaveMultipleNoFail()
    {
        /* @var $cache \Doctrine\Common\Cache\CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
        $cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            [],
            '',
            true,
            true,
            true,
            ['doSave']
        );

        $cache
            ->expects($this->at(1))
            ->method('doSave')
            ->with('[kerr][1]', 'verr', 0)
            ->will($this->returnValue(false));

        $cache
            ->expects($this->at(2))
            ->method('doSave')
            ->with('[kok][1]', 'vok', 0)
            ->will($this->returnValue(true));

        $cache->saveMultiple([
            'kerr'  => 'verr',
            'kok'   => 'vok',
        ]);
    }

    public function testDeleteMultipleNoFail()
    {
        /* @var $cache \Doctrine\Common\Cache\CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
        $cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            array(),
            '',
            true,
            true,
            true,
            array('doDelete')
        );

        $cache
            ->expects($this->at(1))
            ->method('doDelete')
            ->with('[kerr][1]')
            ->will($this->returnValue(false));

        $cache
            ->expects($this->at(2))
            ->method('doDelete')
            ->with('[kok][1]')
            ->will($this->returnValue(true));

        $cache->deleteMultiple(array(
            'kerr',
            'kok',
        ));
    }
}
