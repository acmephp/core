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
     * @return resource
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }
}
