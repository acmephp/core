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
     * @var CSR
     */
    private $csrContent;

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
    private $pem;

    /**
     * @param string  $csrContent
     * @param string  $domain
     * @param KeyPair $domainKeyPair
     * @param string  $pem
     */
    public function __construct($csrContent, $domain, KeyPair $domainKeyPair, $pem)
    {
        Assert::stringNotEmpty($domain, 'Certificate::$domain expected a non-empty string. Got: %s');
        Assert::stringNotEmpty($pem, 'Certificate::$fullchain expected a non-empty string. Got: %s');

        $this->csrContent = $csrContent;
        $this->domain = $domain;
        $this->domainKeyPair = $domainKeyPair;
        $this->pem = $pem;
    }

    /**
     * @return string
     */
    public function getCsrContent()
    {
        return $this->csrContent;
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
    public function getPem()
    {
        return $this->pem;
    }
}
