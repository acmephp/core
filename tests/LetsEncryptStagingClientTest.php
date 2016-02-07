<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Tests;

use AcmePhp\Core\LetsEncryptStagingClient;
use AcmePhp\Core\Ssl\KeyPair;
use Psr\Log\LoggerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class LetsEncryptStagingClientTest extends AbstractAcmeClientTest
{
    /**
     * {@inheritdoc}
     */
    protected function createClient(KeyPair $accountKeyPair, LoggerInterface $logger = null)
    {
        return new LetsEncryptStagingClient($accountKeyPair, $logger);
    }
}
