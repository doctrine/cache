<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\PredisCache;
use Predis\Client;
use Predis\ClientInterface;
use Predis\Connection\ConnectionException;

use function assert;
use function class_exists;

class PredisCacheTest extends CacheTest
{
    /** @var Client */
    private $client;

    protected function setUp(): void
    {
        if (! class_exists(Client::class)) {
            $this->markTestSkipped('Predis\Client is missing. Make sure to "composer install" to have all dev dependencies.');
        }

        $this->client = new Client();

        try {
            $this->client->connect();
        } catch (ConnectionException $e) {
            $this->markTestSkipped('Cannot connect to Redis because of: ' . $e);
        }
    }

    public function testHitMissesStatsAreProvided(): void
    {
        $cache = $this->getCacheDriver();
        $stats = $cache->getStats();

        self::assertNotNull($stats[Cache::STATS_HITS]);
        self::assertNotNull($stats[Cache::STATS_MISSES]);
    }

    /**
     * @return PredisCache
     */
    protected function getCacheDriver(): CacheProvider
    {
        return new PredisCache($this->client);
    }

    /**
     * {@inheritDoc}
     *
     * @dataProvider provideDataToCache
     */
    public function testSetContainsFetchDelete($value): void
    {
        if ($value === []) {
            $this->markTestIncomplete(
                'Predis currently doesn\'t support saving empty array values. '
                . 'See https://github.com/nrk/predis/issues/241'
            );
        }

        parent::testSetContainsFetchDelete($value);
    }

    /**
     * {@inheritDoc}
     *
     * @dataProvider provideDataToCache
     */
    public function testUpdateExistingEntry($value): void
    {
        if ($value === []) {
            $this->markTestIncomplete(
                'Predis currently doesn\'t support saving empty array values. '
                . 'See https://github.com/nrk/predis/issues/241'
            );
        }

        parent::testUpdateExistingEntry($value);
    }

    public function testAllowsGenericPredisClient(): void
    {
        $predisClient = $this->createMock(ClientInterface::class);
        assert($predisClient instanceof ClientInterface);

        self::assertInstanceOf(PredisCache::class, new PredisCache($predisClient));
    }
}
