 Doctrine Cache Bloom Filter suggestion
==================

`A Bloom filter is a space-efficient probabilistic data structure, conceived by Burton Howard Bloom in 1970,
that is used to test whether an element is a member of a set (False positive matches are possible, but false negatives are not).`

The idea is to add in front of chain cache Bloom filter. 
It might make sense if there will big enough cache miss ratio.



Example of usage
------------------

```php
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\BloomChainCache;
use RocketLabs\BloomFilter\Persist\Redis as RedisPersister;
use RocketLabs\BloomFilter\DynamicBloomFilter;
use RocketLabs\BloomFilter\Hash\Murmur;


$redis = new Redis();
$redis->connect('localhost');

// init chain cache
$redisCache = new RedisCache();
$memCache = new Mem();
$redisCache->setRedis($redis);

$arrayCache = new ArrayCache();
$chainCache = new ChainCache([$arrayCache, $redisCache]);

// init Bloom Filter
$persisterRedis = new RedisPersister($redis, 'dynamic_bloom_filter');
$hashFunction = new Murmur();
$bloomFilter = new DynamicBloomFilter($persisterRedis, $hashFunction);

//restore from memento if exists
//get memento from storage
if (isset($memento)) {
    $bloomFilter->restoreState($memento);
} else {
    $bloomFilter->setSize(10000)->setFalsePositiveProbability(0.01);
}

//Create bloom chain cache
$bloomChainCache = new BloomChainCache($bloomFilter, [$arrayCache, $redisCache]);


$bloomChainCache->save('test1', 'data1');

$bloomChainCache->fetch('test1'); // exists in bloom, continue to chainCache
$bloomChainCache->fetch('test2'); // does not exist in bloom. return false


//Save State between requests
$memento = $bloomFilter->saveState();
//save $memento to some storage

```