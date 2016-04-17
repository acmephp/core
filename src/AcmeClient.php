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

use AcmePhp\Core\Exception\AcmeCoreExceptionHandler;
use AcmePhp\Core\Exception\Protocol\AcmeInvalidResponseException;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Protocol\Challenge;
use AcmePhp\Core\Protocol\ResourcesDirectory;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\KeyPair;
use GuzzleHttp\ClientInterface;

/**
 * ACME protocol client implementation.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeClient implements AcmeClientInterface
{
    /**
     * @var string
     */
    private $directoryUrl;

    /**
     * @var KeyPair
     */
    private $accountKeyPair;

    /**
     * @var SecureHttpClient
     */
    private $secureHttpClient;

    /**
     * @var AcmeCoreExceptionHandler
     */
    private $exceptionHandler;

    /**
     * @var ResourcesDirectory
     */
    private $resourcesDirectory;

    /**
     * @param string $directoryUrl
     * @param KeyPair $accountKeyPair
     * @param ClientInterface|null $httpClient
     */
    public function __construct($directoryUrl, KeyPair $accountKeyPair, ClientInterface $httpClient = null)
    {
        $this->directoryUrl = $directoryUrl;
        $this->accountKeyPair = $accountKeyPair;
        $this->secureHttpClient = new SecureHttpClient($this->accountKeyPair, $httpClient);
        $this->exceptionHandler = new AcmeCoreExceptionHandler();
    }

    /**
     * {@inheritdoc}
     */
    public function registerAccount($email = null)
    {
        // TODO: Implement registerAccount() method.
    }

    /**
     * {@inheritdoc}
     */
    public function requestChallenge($domain)
    {
        // TODO: Implement requestChallenge() method.
    }

    /**
     * {@inheritdoc}
     */
    public function checkChallenge(Challenge $challenge, $timeout = 180)
    {
        // TODO: Implement checkChallenge() method.
    }

    /**
     * {@inheritdoc}
     */
    public function requestCertificate($domain, KeyPair $domainKeyPair, CertificateRequest $csr, $timeout = 180)
    {
        // TODO: Implement requestCertificate() method.
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol
     * to the given resource (URL is found using the server resources directory).
     *
     * @param string $method
     * @param string $resource
     * @param array  $payload
     *
     * @return array|string
     */
    protected function requestResource($method, $resource, array $payload)
    {
        if (! $this->resourcesDirectory) {
            $this->createResourcesDirectory();
        }

        return $this->secureHttpClient->signedRequest(
            $method,
            $this->resourcesDirectory->getResourceUrl($resource),
            $payload
        );
    }

    /**
     * Prepare this client by checking the presence of an account key pair
     * and contacting the server to know the endpoints URLs.
     */
    private function createResourcesDirectory()
    {
        $response = $this->secureHttpClient->unsignedRequest('GET', $this->directoryUrl);

        $resourcesDirectory = \GuzzleHttp\Psr7\copy_to_string($response->getBody());
        $resourcesDirectory = @json_decode($resourcesDirectory, true);

        if (!$resourcesDirectory) {
            throw new AcmeCoreServerMalformedResponseException('GET', $this->directoryUrl, [], $response);
        }

        $this->resourcesDirectory = new ResourcesDirectory($resourcesDirectory);
    }
}
