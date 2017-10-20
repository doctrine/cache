 Doctrine Cache Bloom Filter suggestion
==================

`A Bloom filter is a space-efficient probabilistic data structure, conceived by Burton Howard Bloom in 1970,
that is used to test whether an element is a member of a set (False positive matches are possible, but false negatives are not).`

The idea is to add Bloom filter to the library. Bloom filter can be used in front of chain cache or might be used alone. 

There is 3 implementations of Bloom filter:
-

- Bloom Filter
    - Pros:
        - Fastest filter
        - Use less memory than other implementations
    - Cons:
        - Does not support deletion
        - Does not support resize. Max amount of set members should be known in advance.
- Dynamic Bloom Filter
     - Pros:
        - Amount of members in dynamic
     - Cons:
         - Does not support deletion
         - Slower than Bloom Filter
         - False positive probability increase each time when the filter need to be resized

- Counting Bloom Filter
    - Pros:
        - Supports deleting from filter
    - Cons:
        - Needs more memory. Additional 4 bits or 8 bits per flag
        - Does not support resize. Max amount of set members should be known in advance.


Supported Bloom Filter storages:
- 
 - In Memory 
 - in Redis
 
Supported Hashes
-
 - Murmur3: Better uniform distribution.
 - Crc32b: Faster computation.



Cache Chain - Example of usage
-

```php
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\BloomChainCache;
use Doctrine\Common\Filter\Persist\BitRedis;
use Doctrine\Common\Filter\DynamicBloomFilter;
use Doctrine\Common\Filter\Hash\Murmur;


$redis = new Redis();
$redis->connect('localhost');

// init caches
$redisCache = new RedisCache();
$redisCache->setRedis($redis);
$arrayCache = new ArrayCache();
$chainCache = new ChainCache([$arrayCache, $redisCache]);

// init Bloom Filter
$persisterRedis = new BitRedis($redis, 'dynamic_bloom_filter');
$hashFunction = new Murmur();
$bloomFilter = new DynamicBloomFilter($persisterRedis, $hashFunction);

//restore from memento if exists
//get memento from storage
if (isset($memento)) {
    $bloomFilter->restoreState($memento);
} else {
    $bloomFilter->setSize(10000);
}

//Create bloom chain cache
$bloomChainCache = new BloomChainCache($bloomFilter, $arrayCache, $redisCache);


$bloomChainCache->save('test1', 'data1');

$bloomChainCache->fetch('test1'); // exists in bloom, continue to chainCache
$bloomChainCache->fetch('test2'); // does not exist in bloom. return false


//Save State between requests
$memento = $bloomFilter->saveState();
//save $memento to some storage

```

Counting Bloom Filter Example of usage
-
```php

use Doctrine\Common\Filter\Persist\BitRedis;
use Doctrine\Common\Filter\CountingBloomFilter;
use Doctrine\Common\Filter\Hash\Murmur;


$redis = new Redis();
$redis->connect('localhost');

// init Bloom Filter
$persisterRedis = new BitRedis($redis, 'dynamic_bloom_filter');
$hashFunction = new Murmur();
$bloomFilter = new DynamicBloomFilter($persisterRedis, $hashFunction);

$bloomFilter->add('test1');
$bloomFilter->has('test1'); // returns true //Probably in filter
$bloomFilter->delete('test1');
$bloomFilter->has('test1'); // returns false //Defenetly not in filter
```