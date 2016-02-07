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
 * Manipulate SSL key pairs.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class KeyPairManager
{
    /**
     * @var string
     */
    private $keyPairsDirectory;

    /**
     * @var KeyPair
     */
    private $accountKeyPair;

    /**
     * @param string $keyPairsDirectory
     */
    public function __construct($keyPairsDirectory)
    {
        Assert::stringNotEmpty($keyPairsDirectory, 'The path where to store SSL key-pairs should be a string. Got: %s');
        Assert::readable($keyPairsDirectory, 'The path where to store SSL key-pairs should be readable.');
        Assert::writable($keyPairsDirectory, 'The path where to store SSL key-pairs should be writable.');

        $this->keyPairsDirectory = $keyPairsDirectory;
    }

    /**
     * @return KeyPair
     */
    public function getAccountKeyPair()
    {
        if (!$this->accountKeyPair) {
            $this->accountKeyPair = $this->load('_account');
        }

        if (!$this->accountKeyPair) {
            $this->accountKeyPair = $this->generate('_account');
        }

        return $this->accountKeyPair;
    }

    /**
     * Load a KeyPair by its name.
     *
     * @param string $name
     *
     * @throws \RuntimeException
     *
     * @return KeyPair|null
     */
    public function load($name)
    {
        $publicKeyFile = $this->keyPairsDirectory.'/'.$name.'/public.pem';
        $privateKeyFile = $this->keyPairsDirectory.'/'.$name.'/private.pem';

        if (!file_exists($publicKeyFile) || !file_exists($privateKeyFile)) {
            return;
        }

        if (false === ($publicKey = openssl_pkey_get_public('file://'.$publicKeyFile))) {
            throw new \RuntimeException(sprintf(
                'Reading of the public key file "%s" failed with message: %s',
                $publicKeyFile,
                openssl_error_string()
            ));
        }

        if (false === ($privateKey = openssl_pkey_get_private('file://'.$privateKeyFile))) {
            throw new \RuntimeException(sprintf(
                'Reading of the private key file "%s" failed with message: %s',
                $privateKeyFile,
                openssl_error_string()
            ));
        }

        return new KeyPair($name, $publicKey, $privateKey);
    }

    /**
     * Geenerate a KeyPair for the given name.
     *
     * @param string $name
     *
     * @throws \RuntimeException
     *
     * @return KeyPair
     */
    public function generate($name)
    {
        $keyDir = $this->keyPairsDirectory.'/'.$name;
        $publicKeyFile = $keyDir.'/public.pem';
        $privateKeyFile = $keyDir.'/private.pem';

        if (file_exists($publicKeyFile) && file_exists($privateKeyFile)) {
            return;
        }

        if (!@mkdir($keyDir, 0777, true) && !is_dir($keyDir)) {
            throw new \RuntimeException(sprintf('The directory "%s" could not be created', $keyDir));
        }

        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 4096]);

        if (!openssl_pkey_export($key, $privateKey)) {
            throw new \RuntimeException(sprintf('OpenSSL key export failed during generation of key "%s"', $name));
        }

        $details = openssl_pkey_get_details($key);

        file_put_contents($publicKeyFile, $details['key']);
        file_put_contents($privateKeyFile, $privateKey);

        return $this->load($name);
    }
}
