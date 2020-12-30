<?php

namespace Doctrine\Tests\Common\Cache;

use ArrayObject;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Tests\DoctrineTestCase;
use stdClass;

use function array_keys;
use function array_map;
use function array_slice;
use function is_object;
use function restore_error_handler;
use function set_error_handler;
use function sleep;
use function sprintf;
use function str_repeat;

abstract class CacheTest extends DoctrineTestCase
{
    /**
     * @param mixed $value
     *
     * @dataProvider provideDataToCache
     */
    public function testSetContainsFetchDelete($value): void
    {
        $cache = $this->getCacheDriver();

        // Test saving a value, checking if it exists, and fetching it back
        self::assertTrue($cache->save('key', $value));
        self::assertTrue($cache->contains('key'));
        if (is_object($value)) {
            self::assertEquals($value, $cache->fetch('key'), 'Objects retrieved from the cache must be equal but not necessarily the same reference');
        } else {
            self::assertSame($value, $cache->fetch('key'), 'Scalar and array data retrieved from the cache must be the same as the original, e.g. same type');
        }

        // Test deleting a value
        self::assertTrue($cache->delete('key'));
        self::assertFalse($cache->contains('key'));
        self::assertFalse($cache->fetch('key'));
    }

    /**
     * @param mixed $value
     *
     * @dataProvider provideDataToCache
     */
    public function testUpdateExistingEntry($value): void
    {
        $cache = $this->getCacheDriver();

        self::assertTrue($cache->save('key', 'old-value'));
        self::assertTrue($cache->contains('key'));

        self::assertTrue($cache->save('key', $value));
        self::assertTrue($cache->contains('key'));
        if (is_object($value)) {
            self::assertEquals($value, $cache->fetch('key'), 'Objects retrieved from the cache must be equal but not necessarily the same reference');
        } else {
            self::assertSame($value, $cache->fetch('key'), 'Scalar and array data retrieved from the cache must be the same as the original, e.g. same type');
        }
    }

    public function testCacheKeyIsCaseSensitive(): void
    {
        $cache = $this->getCacheDriver();

        self::assertTrue($cache->save('key', 'value'));
        self::assertTrue($cache->contains('key'));
        self::assertSame('value', $cache->fetch('key'));

        self::assertFalse($cache->contains('KEY'));
        self::assertFalse($cache->fetch('KEY'));

        $cache->delete('KEY');
        self::assertTrue($cache->contains('key'), 'Deleting cache item with different case must not affect other cache item');
    }

    public function testFetchMultiple(): void
    {
        $cache  = $this->getCacheDriver();
        $values = $this->provideDataToCache();
        $saved  = [];

        foreach ($values as $key => $value) {
            $cache->save($key, $value[0]);

            $saved[$key] = $value[0];
        }

        $keys = array_keys($saved);

        self::assertEquals(
            $saved,
            $cache->fetchMultiple($keys),
            'Testing fetchMultiple with different data types'
        );
        self::assertEquals(
            array_slice($saved, 0, 1),
            $cache->fetchMultiple(array_slice($keys, 0, 1)),
            'Testing fetchMultiple with a single key'
        );

        $keysWithNonExisting   = [];
        $keysWithNonExisting[] = 'non_existing1';
        $keysWithNonExisting[] = $keys[0];
        $keysWithNonExisting[] = 'non_existing2';
        $keysWithNonExisting[] = $keys[1];
        $keysWithNonExisting[] = 'non_existing3';

        self::assertEquals(
            array_slice($saved, 0, 2),
            $cache->fetchMultiple($keysWithNonExisting),
            'Testing fetchMultiple with a subset of keys and mixed with non-existing ones'
        );
    }

    public function testFetchMultipleWithNoKeys(): void
    {
        $cache = $this->getCacheDriver();

        self::assertSame([], $cache->fetchMultiple([]));
    }

    public function testSaveMultiple(): void
    {
        $cache = $this->getCacheDriver();
        $cache->deleteAll();

        $data = array_map(static function ($value) {
            return $value[0];
        }, $this->provideDataToCache());

        self::assertTrue($cache->saveMultiple($data));

        $keys = array_keys($data);

        self::assertEquals($data, $cache->fetchMultiple($keys));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public function provideDataToCache(): array
    {
        $obj       = new stdClass();
        $obj->foo  = 'bar';
        $obj2      = new stdClass();
        $obj2->bar = 'foo';
        $obj2->obj = $obj;
        $obj->obj2 = $obj2;

        return [
            'array' => [['one', 2, 3.01]],
            'string' => ['value'],
            'string_invalid_utf8' => ["\xc3\x28"],
            'string_null_byte' => ['with' . "\0" . 'null char'],
            'integer' => [1],
            'float' => [1.5],
            'object' => [new ArrayObject(['one', 2, 3.01])],
            'object_recursive' => [$obj],
            'true' => [true],
            // the following are considered FALSE in boolean context, but caches should still recognize their existence
            'null' => [null],
            'false' => [false],
            'array_empty' => [[]],
            'string_zero' => ['0'],
            'integer_zero' => [0],
            'float_zero' => [0.0],
            'string_empty' => [''],
        ];
    }

    public function testDeleteIsSuccessfulWhenKeyDoesNotExist(): void
    {
        $cache = $this->getCacheDriver();

        $cache->delete('key');
        self::assertFalse($cache->contains('key'));
        self::assertTrue($cache->delete('key'));
    }

    public function testDeleteAll(): void
    {
        $cache = $this->getCacheDriver();

        self::assertTrue($cache->save('key1', 1));
        self::assertTrue($cache->save('key2', 2));
        self::assertTrue($cache->deleteAll());
        self::assertFalse($cache->contains('key1'), sprintf(
            'key1 should have disappeared but did not. The namespace is "%s"',
            $cache->getNamespace()
        ));
        self::assertFalse($cache->contains('key2'));
    }

    public function testDeleteMulti(): void
    {
        $cache = $this->getCacheDriver();

        self::assertTrue($cache->save('key1', 1));
        self::assertTrue($cache->save('key2', 1));
        self::assertTrue($cache->deleteMultiple(['key1', 'key2', 'key3']));
        self::assertFalse($cache->contains('key1'));
        self::assertFalse($cache->contains('key2'));
        self::assertFalse($cache->contains('key3'));
    }

    /**
     * @dataProvider provideCacheIds
     */
    public function testCanHandleSpecialCacheIds(string $id): void
    {
        $cache = $this->getCacheDriver();

        self::assertTrue($cache->save($id, 'value'));
        self::assertTrue($cache->contains($id));
        self::assertEquals('value', $cache->fetch($id));

        self::assertTrue($cache->delete($id));
        self::assertFalse($cache->contains($id));
        self::assertFalse($cache->fetch($id));
    }

    public function testNoCacheIdCollisions(): void
    {
        $cache = $this->getCacheDriver();

        $ids = $this->provideCacheIds();

        // fill cache with each id having a different value
        foreach ($ids as $index => $id) {
            $cache->save($id[0], $index);
        }

        // then check value of each cache id
        foreach ($ids as $index => $id) {
            $value = $cache->fetch($id[0]);
            self::assertNotFalse($value, sprintf('Failed to retrieve data for cache id "%s".', $id[0]));
            if ($index === $value) {
                continue;
            }

            $this->fail(sprintf('Cache id "%s" collides with id "%s".', $id[0], $ids[$value][0]));
        }
    }

    /**
     * Returns cache ids with special characters that should still work.
     *
     * For example, the characters :\/<>"*?| are not valid in Windows filenames. So they must be encoded properly.
     * Each cache id should be considered different from the others.
     *
     * @psalm-return list<array{string}>
     */
    public function provideCacheIds(): array
    {
        return [
            [':'],
            ['\\'],
            ['/'],
            ['<'],
            ['>'],
            ['"'],
            ['*'],
            ['?'],
            ['|'],
            ['['],
            [']'],
            ['ä'],
            ['a'],
            ['é'],
            ['e'],
            ['.'], // directory traversal
            ['..'], // directory traversal
            ['-'],
            ['_'],
            ['$'],
            ['%'],
            [' '],
            ["\0"],
            [''],
            [str_repeat('a', 300)], // long key
            [str_repeat('a', 113)],
        ];
    }

    public function testLifetime(): void
    {
        $cache = $this->getCacheDriver();
        $cache->save('expire', 'value', 1);
        self::assertTrue($cache->contains('expire'), 'Data should not be expired yet');
        // @TODO should more TTL-based tests pop up, so then we should mock the `time` API instead
        sleep(2);
        self::assertFalse($cache->contains('expire'), 'Data should be expired');
    }

    public function testNoExpire(): void
    {
        $cache = $this->getCacheDriver();
        $cache->save('noexpire', 'value', 0);
        // @TODO should more TTL-based tests pop up, so then we should mock the `time` API instead
        sleep(1);
        self::assertTrue($cache->contains('noexpire'), 'Data with lifetime of zero should not expire');
    }

    public function testLongLifetime(): void
    {
        $cache = $this->getCacheDriver();
        $cache->save('longlifetime', 'value', 30 * 24 * 3600 + 1);
        self::assertTrue($cache->contains('longlifetime'), 'Data with lifetime > 30 days should be accepted');
    }

    public function testDeleteAllAndNamespaceVersioningBetweenCaches(): void
    {
        if (! $this->isSharedStorage()) {
            $this->markTestSkipped('The cache storage needs to be shared.');
        }

        $cache1 = $this->getCacheDriver();
        $cache2 = $this->getCacheDriver();

        self::assertTrue($cache1->save('key1', 1));
        self::assertTrue($cache2->save('key2', 2));

        /* Both providers are initialized with the same namespace version, so
         * they can see entries set by each other.
         */
        self::assertTrue($cache1->contains('key1'));
        self::assertTrue($cache1->contains('key2'));
        self::assertTrue($cache2->contains('key1'));
        self::assertTrue($cache2->contains('key2'));

        /* Deleting all entries through one provider will only increment the
         * namespace version on that object (and in the cache itself, which new
         * instances will use to initialize). The second provider will retain
         * its original version and still see stale data.
         */
        self::assertTrue($cache1->deleteAll());
        self::assertFalse($cache1->contains('key1'));
        self::assertFalse($cache1->contains('key2'));
        self::assertTrue($cache2->contains('key1'));
        self::assertTrue($cache2->contains('key2'));

        /* A new cache provider should not see the deleted entries, since its
         * namespace version will be initialized.
         */
        $cache3 = $this->getCacheDriver();
        self::assertFalse($cache3->contains('key1'));
        self::assertFalse($cache3->contains('key2'));
    }

    public function testFlushAll(): void
    {
        $cache = $this->getCacheDriver();

        self::assertTrue($cache->save('key1', 1));
        self::assertTrue($cache->save('key2', 2));
        self::assertTrue($cache->flushAll());
        self::assertFalse($cache->contains('key1'));
        self::assertFalse($cache->contains('key2'));
    }

    public function testFlushAllAndNamespaceVersioningBetweenCaches(): void
    {
        if (! $this->isSharedStorage()) {
            $this->markTestSkipped('The cache storage needs to be shared.');
        }

        $cache1 = $this->getCacheDriver();
        $cache2 = $this->getCacheDriver();

        /* Deleting all elements from the first provider should increment its
         * namespace version before saving the first entry.
         */
        $cache1->deleteAll();
        self::assertTrue($cache1->save('key1', 1));

        /* The second provider will be initialized with the same namespace
         * version upon its first save operation.
         */
        self::assertTrue($cache2->save('key2', 2));

        /* Both providers have the same namespace version and can see entries
         * set by each other.
         */
        self::assertTrue($cache1->contains('key1'));
        self::assertTrue($cache1->contains('key2'));
        self::assertTrue($cache2->contains('key1'));
        self::assertTrue($cache2->contains('key2'));

        /* Flushing all entries through one cache will remove all entries from
         * the cache but leave their namespace version as-is.
         */
        self::assertTrue($cache1->flushAll());
        self::assertFalse($cache1->contains('key1'));
        self::assertFalse($cache1->contains('key2'));
        self::assertFalse($cache2->contains('key1'));
        self::assertFalse($cache2->contains('key2'));

        /* Inserting a new entry will use the same, incremented namespace
         * version, and it will be visible to both providers.
         */
        self::assertTrue($cache1->save('key1', 1));
        self::assertTrue($cache1->contains('key1'));
        self::assertTrue($cache2->contains('key1'));

        /* A new cache provider will be initialized with the original namespace
         * version and not share any visibility with the first two providers.
         */
        $cache3 = $this->getCacheDriver();
        self::assertFalse($cache3->contains('key1'));
        self::assertFalse($cache3->contains('key2'));
        self::assertTrue($cache3->save('key3', 3));
        self::assertTrue($cache3->contains('key3'));
    }

    public function testNamespace(): void
    {
        $cache = $this->getCacheDriver();

        $cache->setNamespace('ns1_');

        self::assertTrue($cache->save('key1', 1));
        self::assertTrue($cache->contains('key1'));

        $cache->setNamespace('ns2_');

        self::assertFalse($cache->contains('key1'));
    }

    public function testDeleteAllNamespace(): void
    {
        $cache = $this->getCacheDriver();

        $cache->setNamespace('ns1');
        self::assertFalse($cache->contains('key1'));
        $cache->save('key1', 'test');
        self::assertTrue($cache->contains('key1'));

        $cache->setNamespace('ns2');
        self::assertFalse($cache->contains('key1'));
        $cache->save('key1', 'test');
        self::assertTrue($cache->contains('key1'));

        $cache->setNamespace('ns1');
        self::assertTrue($cache->contains('key1'));
        $cache->deleteAll();
        self::assertFalse($cache->contains('key1'));

        $cache->setNamespace('ns2');
        self::assertTrue($cache->contains('key1'));
        $cache->deleteAll();
        self::assertFalse($cache->contains('key1'));
    }

    /**
     * @group DCOM-43
     */
    public function testGetStats(): void
    {
        $cache = $this->getCacheDriver();
        $stats = $cache->getStats();

        self::assertArrayHasKey(Cache::STATS_HITS, $stats);
        self::assertArrayHasKey(Cache::STATS_MISSES, $stats);
        self::assertArrayHasKey(Cache::STATS_UPTIME, $stats);
        self::assertArrayHasKey(Cache::STATS_MEMORY_USAGE, $stats);
        self::assertArrayHasKey(Cache::STATS_MEMORY_AVAILABLE, $stats);
    }

    public function testSaveReturnsTrueWithAndWithoutTTlSet(): void
    {
        $cache = $this->getCacheDriver();
        $cache->deleteAll();
        self::assertTrue($cache->save('without_ttl', 'without_ttl'));
        self::assertTrue($cache->save('with_ttl', 'with_ttl', 3600));
    }

    public function testValueThatIsFalseBooleanIsProperlyRetrieved()
    {
        $cache = $this->getCacheDriver();
        $cache->deleteAll();

        self::assertTrue($cache->save('key1', false));
        self::assertTrue($cache->contains('key1'));
        self::assertFalse($cache->fetch('key1'));
    }

    /**
     * @group 147
     * @group 152
     */
    public function testFetchingANonExistingKeyShouldNeverCauseANoticeOrWarning(): void
    {
        $cache = $this->getCacheDriver();

        $errorHandler = function () {
            restore_error_handler();

            $this->fail('include failure captured');
        };

        set_error_handler($errorHandler);

        $cache->fetch('key');

        self::assertSame(
            $errorHandler,
            set_error_handler(static function () {
            }),
            'The error handler is the one set by this test, and wasn\'t replaced'
        );

        restore_error_handler();
        restore_error_handler();
    }

    /**
     * Return whether multiple cache providers share the same storage.
     *
     * This is used for skipping certain tests for shared storage behavior.
     */
    protected function isSharedStorage(): bool
    {
        return true;
    }

    abstract protected function getCacheDriver(): CacheProvider;
}
