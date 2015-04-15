<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\PredisCache;
use Predis\Client;
use Predis\Connection\ConnectionException;

class PredisCacheTest extends CacheTest
{
    private $client;

    public function setUp()
    {
        $this->client = new Client();

        try {
            $this->client->connect();
        } catch (ConnectionException $e) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of redis');
        }
    }

    /**
     * @return PredisCache
     */
    protected function _getCacheDriver()
    {
        return new PredisCache($this->client);
    }

    /**
     * {@inheritDoc}
     *
     * @dataProvider falseCastedValuesProvider
     */
    public function testFalseCastedValues($value)
    {
        if (array() === $value) {
            $this->markTestIncomplete(
                'Predis currently doesn\'t support saving empty array values. '
                . 'See https://github.com/nrk/predis/issues/241'
            );
        }

        parent::testFalseCastedValues($value);
    }
}
