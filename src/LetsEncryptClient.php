<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core;

/**
 * Let's Encrypt client.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class LetsEncryptClient extends AbstractAcmeClient
{
    /**
     * @return array
     */
    public static function getLetsEncryptCertificateChain()
    {
        return [
            __DIR__.'/../res/letsencrypt-x1.pem',
            __DIR__.'/../res/letsencrypt-root.pem',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCABaseUrl()
    {
        return 'https://acme-v01.api.letsencrypt.org';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCALicense()
    {
        return 'https://letsencrypt.org/documents/LE-SA-v1.0.1-July-27-2015.pdf';
    }

    /**
     * {@inheritdoc}
     */
    public function getCertificatesChain()
    {
        return self::getLetsEncryptCertificateChain();
    }
}
