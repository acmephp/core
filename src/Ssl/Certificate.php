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
 * Represent an issued SSL certificate.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Certificate
{
    /**
     * @var string
     */
    private $domain;

    /**
     * @var KeyPair
     */
    private $domainKeyPair;

    /**
     * @var string
     */
    private $fullchain;

    /**
     * @var string
     */
    private $cert;

    /**
     * @var string
     */
    private $chain;

    /**
     * @param string $domain
     * @param KeyPair $domainKeyPair
     * @param string $fullchain
     * @param string $cert
     * @param string $chain
     */
    public function __construct($domain, KeyPair $domainKeyPair, $fullchain, $cert, $chain)
    {
        Assert::stringNotEmpty($domain, 'Certificate::$domain expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($fullchain, 'Certificate::$fullchain expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($cert, 'Certificate::$cert expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($chain, 'Certificate::$chain expected a non-empty string. Got: %s');

        $this->domain = $domain;
        $this->domainKeyPair = $domainKeyPair;
        $this->fullchain = $fullchain;
        $this->cert = $cert;
        $this->chain = $chain;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return KeyPair
     */
    public function getDomainKeyPair()
    {
        return $this->domainKeyPair;
    }

    /**
     * @return string
     */
    public function getFullchain()
    {
        return $this->fullchain;
    }

    /**
     * @return string
     */
    public function getCert()
    {
        return $this->cert;
    }

    /**
     * @return string
     */
    public function getChain()
    {
        return $this->chain;
    }
}
