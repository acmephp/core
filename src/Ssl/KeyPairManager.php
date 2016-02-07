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

use AcmePhp\Core\Ssl\Exception\GeneratingSslKeyFailedException;
use AcmePhp\Core\Ssl\Exception\LoadingSslKeyFailedException;

/**
 * Load and generate KeyPair objects using OpenSSL.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class KeyPairManager
{
    /**
     * Load a KeyPair.
     *
     * @param string $publicKeyFile  The path to the public key file.
     * @param string $privateKeyFile The path to the private key file.
     *
     * @throws LoadingSslKeyFailedException If OpenSSL failed to load the keys.
     *
     * @return KeyPair
     */
    public function loadKeyPair($publicKeyFile, $privateKeyFile)
    {
        return self::load($publicKeyFile, $privateKeyFile);
    }

    /**
     * Load a KeyPair.
     *
     * @param string $publicKeyFile  The path to the public key file.
     * @param string $privateKeyFile The path to the private key file.
     *
     * @throws LoadingSslKeyFailedException If OpenSSL failed to load the keys.
     *
     * @return KeyPair
     */
    public static function load($publicKeyFile, $privateKeyFile)
    {
        if (!file_exists($publicKeyFile)) {
            throw new LoadingSslKeyFailedException('public', $publicKeyFile, 'file does not exists');
        }

        if (!file_exists($privateKeyFile)) {
            throw new LoadingSslKeyFailedException('private', $privateKeyFile, 'file does not exists');
        }

        if (false === ($publicKey = openssl_pkey_get_public('file://'.$publicKeyFile))) {
            throw new LoadingSslKeyFailedException('public', $publicKeyFile, openssl_error_string());
        }

        if (false === ($privateKey = openssl_pkey_get_private('file://'.$privateKeyFile))) {
            throw new LoadingSslKeyFailedException('public', $privateKeyFile, openssl_error_string());
        }

        return new KeyPair($publicKey, $privateKey);
    }

    /**
     * Geenrate a KeyPair.
     *
     * @throws GeneratingSslKeyFailedException If OpenSSL failed to generate keys.
     *
     * @return KeyPair
     */
    public function generateKeyPair()
    {
        return self::generate();
    }

    /**
     * Geenrate a KeyPair.
     *
     * @throws GeneratingSslKeyFailedException If OpenSSL failed to generate keys.
     *
     * @return KeyPair
     */
    public static function generate()
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 4096,
        ]);

        if (!openssl_pkey_export($key, $privateKey)) {
            throw new GeneratingSslKeyFailedException(sprintf(
                'OpenSSL key export failed during generation with error: %s',
                openssl_error_string()
            ));
        }

        $details = openssl_pkey_get_details($key);

        return new KeyPair(openssl_pkey_get_public($details['key']), openssl_pkey_get_private($privateKey));
    }
}
