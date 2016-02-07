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

use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Ssl\KeyPairManager;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * ACME protocol client.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeClient
{
    /**
     * @var string
     */
    private $authority;

    /**
     * @var string
     */
    private $license;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KeyPairManager
     */
    private $keyPairManager;

    /**
     * @var SecureHttpClient
     */
    private $httpClient;

    /**
     * @param string $authority ACME certificate authority.
     * @param string $license ACME certificate authority license URL.
     * @param $keyPairsDirectory
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        $authority,
        $license,
        $keyPairsDirectory,
        LoggerInterface $logger = null
    ) {
        Assert::stringNotEmpty($authority, 'ACME certificate authority URL should be a string. Got: %s');
        Assert::stringNotEmpty($authority, 'ACME certificate authority license should be a string. Got: %s');

        $this->authority = $authority;
        $this->license = $license;
        $this->keyPairManager = new KeyPairManager($keyPairsDirectory);
        $this->httpClient = new SecureHttpClient($this->authority, $this->keyPairManager->getAccountKeyPair());
        $this->logger = $logger;
    }

    /**
     * Registering the local account into the Certificate Authority
     *
     * @param string|null $email
     *
     * @return array
     */
    public function registerAccount($email = null)
    {
        $payload = [];
        $payload['resource'] = 'new-reg';
        $payload['agreement'] = $this->license;

        if ($email) {
            $payload['contact'] = [ 'mailto:'.$email ];
        }

        $this->log('info', sprintf('Registering account with payload %s', json_encode($payload)));

        return $this->httpClient->request('/acme/new-reg', $payload);
    }

    /**
     * Request the challenge of a given domain.
     * Returns a Token to expose on a specific path to validate the domain.
     *
     * @param $domain
     */
    public function requestChallenge($domain)
    {
        $this->log('info', sprintf('Requesting challenge for domain "%s"', $domain));

        $response = $this->httpClient->request('/acme/new-authz', [
            'resource' => 'new-authz',
            'identifier' => [
                'type' => 'dns',
                'value' => $domain,
            ],
        ]);



        var_dump($response);
        exit;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private function log($level, $message, array $context = [])
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
