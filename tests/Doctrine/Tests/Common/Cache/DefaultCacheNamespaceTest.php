<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Tests\DoctrineTestCase;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\DefaultCacheNamespace;

class DefaultCacheNamespaceTest extends DoctrineTestCase
{
    /**
     * @var \Doctrine\Common\Cache\DefaultCacheNamespace
     */
    private $cacheNamespace;

    protected function setUp()
    {
        parent::setUp();

        $this->cacheNamespace = new DefaultCacheNamespace(new ArrayCache());
    }

    public function testChangeNamespace()
    {
        $this->cacheNamespace->setNamespace('foo');

        $this->assertEquals('foo', $this->cacheNamespace->getNamespace());
        $this->assertEquals('foo_key1_1', $this->cacheNamespace->getNamespacedKey('key1'));

        $this->cacheNamespace->setNamespace('bar');

        $this->assertEquals('bar', $this->cacheNamespace->getNamespace());
        $this->assertEquals('bar_key1_1', $this->cacheNamespace->getNamespacedKey('key1'));
    }

    public function testChangeFormat()
    {
        $this->cacheNamespace->setFormat('entry[%s][%s][%s]');
        $this->cacheNamespace->setNamespace('ns');

        $this->assertEquals('entry[%s][%s][%s]', $this->cacheNamespace->getFormat());
        $this->assertEquals('entry[ns][key1][1]', $this->cacheNamespace->getNamespacedKey('key1'));
        $this->assertEquals('entry[ns][key2][1]', $this->cacheNamespace->getNamespacedKey('key2'));

        $this->cacheNamespace->setFormat('entry.%s.%s.%s');

        $this->assertEquals('entry.%s.%s.%s', $this->cacheNamespace->getFormat());
        $this->assertEquals('entry.ns.key1.1', $this->cacheNamespace->getNamespacedKey('key1'));
        $this->assertEquals('entry.ns.key2.1', $this->cacheNamespace->getNamespacedKey('key2'));
    }

    public function testIncrementNamespaceVersion()
    {
        $this->cacheNamespace->setNamespace('ns');

        $this->assertEquals('ns_key1_1', $this->cacheNamespace->getNamespacedKey('key1'));
        $this->assertEquals('ns_key2_1', $this->cacheNamespace->getNamespacedKey('key2'));

        $this->cacheNamespace->increment();

        $this->assertEquals('ns_key1_2', $this->cacheNamespace->getNamespacedKey('key1'));
        $this->assertEquals('ns_key2_2', $this->cacheNamespace->getNamespacedKey('key2'));
    }
}