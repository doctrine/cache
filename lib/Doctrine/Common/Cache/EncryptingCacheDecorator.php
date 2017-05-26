<?php
namespace Doctrine\Common\Cache;

use InvalidArgumentException as IAE;
use RuntimeException as RE;

class EncryptingCacheDecorator implements Cache
{
    /** @var Cache */
    private $decorated;
    /** @var resource */
    private $publicKey;
    /** @var resource */
    private $privateKey;

    /**
     * @param Cache $decorated
     * @param resource $publicKey
     * @param resource $privateKey
     *
     * @throws RE If OpenSSL extension not installed.
     * @throws IAE If either key is not an OpenSSL Key resource.
     */
    public function __construct(Cache $decorated, $publicKey, $privateKey)
    {
        if (!extension_loaded('openssl')) {
            throw new RE('The OpenSSL extension is required to use the'
                . __CLASS__ . '. Please install OpenSSL to use this feature.');
        }

        foreach (array($publicKey, $privateKey) as $key) {
            if (!is_resource($key) || 'OpenSSL key' !== get_resource_type($key)) {
                throw new IAE('Keys passed to ' . __CLASS__ . 'must be OpenSSL'
                    . ' key resources.');
            }
        }

        $this->decorated = $decorated;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $stored = $this->decorated->fetch($id);
        if (isset($stored['data'])) {
            openssl_private_decrypt($stored['data'], $decrypted, $this->privateKey);
            return unserialize($decrypted);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $ttl = 0)
    {
        openssl_public_encrypt(serialize($data), $encrypted, $this->publicKey);

        return $this->decorated
            ->save($id, array('data' => $encrypted), $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->decorated
            ->contains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return $this->decorated
            ->getStats();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->decorated
            ->delete($id);
    }
}
