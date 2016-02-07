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

use Psr\Log\LoggerInterface;

/**
 * Let's Encrypt implementation of the AcmeClient.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class LetsEncryptClient extends AcmeClient
{
    const AUTHORITY = 'https://acme-v01.api.letsencrypt.org';
    const LICENSE = 'https://letsencrypt.org/documents/LE-SA-v1.0.1-July-27-2015.pdf';

    /**
     * @param string $keyPairsDirectory
     * @param LoggerInterface|null $logger
     */
    public function __construct($keyPairsDirectory, LoggerInterface $logger = null) {
        parent::__construct(self::AUTHORITY, self::LICENSE, $keyPairsDirectory, $logger);
    }
}
