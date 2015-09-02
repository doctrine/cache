<?php
namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\EncryptingCacheDecorator;
use Doctrine\Tests\DoctrineTestCase;

/**
 * @requires extension openssl
 */
class EncryptingCacheDecoratorTest extends DoctrineTestCase
{
    /** @var resource */
    private static $publicKey;
    /** @var resource */
    private static $privateKey;
    /** @var \PHPUnit_Framework_MockObject_MockObject|Cache */
    private $decorated;
    /** @var EncryptingCacheDecorator */
    private $instance;

    public static function setUpBeforeClass()
    {
        self::$privateKey = openssl_pkey_new();

        $csr = openssl_csr_new(array(), self::$privateKey);
        $x509 = openssl_csr_sign($csr, null, self::$privateKey, 1);
        openssl_x509_export($x509, $cert);
        self::$publicKey = openssl_pkey_get_public($cert);
        openssl_x509_free($x509);
    }

    public function setUp()
    {
        $this->decorated = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->instance = new EncryptingCacheDecorator(
            $this->decorated,
            self::$publicKey,
            self::$privateKey
        );
    }

    public static function tearDownAfterClass()
    {
        openssl_pkey_free(self::$privateKey);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testVerifiesKeysAreResources()
    {
        new EncryptingCacheDecorator($this->decorated, 'string', 1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testVerifiesKeyResourcesAreOpenSslKeys()
    {
        new EncryptingCacheDecorator(
            $this->decorated,
            fopen('php://temp', 'r'),
            fopen('php://memory', 'r')
        );
    }

    public function testProxiesContainsCallsToDecoratedCache()
    {
        $id = microtime();

        $this->decorated->expects($this->once())
            ->method('contains')
            ->with($id);
        $this->instance->contains($id);
    }

    public function testProxiesDeleteCallsToDecoratedCache()
    {
        $id = microtime();

        $this->decorated->expects($this->once())
            ->method('delete')
            ->with($id);
        $this->instance->delete($id);
    }

    public function testProxiesFetchStatCallsToDecoratedCache()
    {
        $this->decorated->expects($this->once())
            ->method('getStats')
            ->with();
        $this->instance->getStats();
    }

    /**
     * @dataProvider cacheableDataProvider
     *
     * @param mixed $data
     */
    public function testEncryptsDataBeforePassingToDecoratedCache($data)
    {
        $privateKey = self::$privateKey;
        $id = microtime();

        $this->decorated->expects($this->once())
            ->method('save')
            ->with(
                $this->equalTo($id),
                $this->callback(function ($arg) use ($privateKey, $data) {
                    if ($arg == $data) {
                        return false;
                    }

                    openssl_private_decrypt($arg['data'], $decrypted, $privateKey);
                    return unserialize($decrypted) == $data;
                }),
                $this->equalTo(0)
            );
        $this->instance
            ->save($id, $data, 0);
    }

    /**
     * @dataProvider cacheableDataProvider
     *
     * @param mixed $data
     */
    public function testDecryptsDataFetchedDecoratedCache($data)
    {
        $this->decorated = new ArrayCache;
        $this->instance = new EncryptingCacheDecorator(
            $this->decorated,
            self::$publicKey,
            self::$privateKey
        );
        $id = microtime();

        $this->instance
            ->save($id, $data, 0);
        $this->assertNotEquals($data, $this->decorated->fetch($id));
        $this->assertEquals($data, $this->instance->fetch($id));
    }

    public function testReturnsFalseWhenFetchCalledWithUnrecognizedKey()
    {
        $this->assertFalse($this->instance->fetch('Kalamazoo'));
    }

    public function cacheableDataProvider()
    {
        return array(
            array(1),
            array('string'),
            array(array('key' => 'value')),
            array(array('one', 2, 3.0)),
            array(new \ArrayObject()),
        );
    }
}
