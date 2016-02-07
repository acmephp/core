<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Ssl;

use AcmePhp\Core\Ssl\Exception\AcmeSslException;
use Webmozart\Assert\Assert;

/**
 * Represent a SSL key-pair (public and private).
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class KeyPair
{
    /**
     * @var resource
     */
    private $publicKey;

    /**
     * @var resource
     */
    private $privateKey;

    /**
     * @param resource $publicKey
     * @param resource $privateKey
     */
    public function __construct($publicKey, $privateKey)
    {
        Assert::notEmpty($publicKey, 'KeyPair::$publicKey should not be empty');
        Assert::notEmpty($privateKey, 'KeyPair::$privateKey should not be empty');
        Assert::resource($publicKey, 'OpenSSL key', 'KeyPair::$publicKey should be a resource of type %2$s. Got: %s');
        Assert::resource($privateKey, 'OpenSSL key', 'KeyPair::$privateKey should be a resource of type %2$s. Got: %s');

        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * @return resource
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getPublicKeyAsPEM()
    {
        $details = openssl_pkey_get_details($this->getPrivateKey());

        return $details['key'];
    }

    /**
     * @return resource
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * @return string
     */
    public function getPrivateKeyAsPEM()
    {
        if (!openssl_pkey_export($this->getPrivateKey(), $privateKey)) {
            throw new AcmeSslException(sprintf(
                'OpenSSL key export failed during generation with error: %s',
                openssl_error_string()
            ));
        }

        return $privateKey;
    }
}
