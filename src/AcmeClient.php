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

use AcmePhp\Core\Ssl\KeyPair;
use Psr\Log\LoggerInterface;

/**
 * ACME generic client.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeClient extends AbstractAcmeClient
{
    const VERSION = '1.0.0-alpha3';

    /**
     * @var string
     */
    private $caBaseUrl;

    /**
     * @var string
     */
    private $caLicense;

    /**
     * @var array
     */
    private $certificatesChain;

    /**
     * Create the client.
     *
     * @param string $caBaseUrl The Certificate Authority base URL.
     * @param string $caLicense The Certificate Authority license document URL.
     * @param KeyPair $accountKeyPair The account KeyPair to use for dialog with the Certificate Authority.
     * @param LoggerInterface|null $logger
     * @param array $certificatesChain
     */
    public function __construct(
        $caBaseUrl,
        $caLicense,
        KeyPair $accountKeyPair = null,
        LoggerInterface $logger = null,
        array $certificatesChain = []
    )
    {
        parent::__construct(null, $logger);

        $this->caBaseUrl = $caBaseUrl;
        $this->caLicense = $caLicense;
        $this->certificatesChain = $certificatesChain;

        $this->useAccountKeyPair($accountKeyPair);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCABaseUrl()
    {
        return $this->caBaseUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCALicense()
    {
        return $this->caLicense;
    }

    /**
     * {@inheritdoc}
     */
    public function getCertificatesChain()
    {
        return $this->certificatesChain;
    }
}
