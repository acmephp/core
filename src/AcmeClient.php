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

use AcmePhp\Core\Exception\AcmeCoreClientException;
use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Protocol\HttpChallengeNotSupportedException;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Protocol\Challenge;
use AcmePhp\Core\Protocol\ResourcesDirectory;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\KeyPair;
use Webmozart\Assert\Assert;

/**
 * ACME protocol client implementation.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeClient implements AcmeClientInterface
{
    /**
     * @var SecureHttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $directoryUrl;

    /**
     * @var ResourcesDirectory
     */
    private $directory;

    /**
     * @param SecureHttpClient $httpClient
     * @param string           $directoryUrl
     */
    public function __construct(SecureHttpClient $httpClient, $directoryUrl)
    {
        $this->httpClient = $httpClient;
        $this->directoryUrl = $directoryUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function registerAccount($agreement = null, $email = null)
    {
        Assert::nullOrString($agreement, 'AcmeClient::registerAccount $agreement expected a string or null. Got: %s');
        Assert::nullOrString($email, 'AcmeClient::registerAccount $email expected a string or null. Got: %s');

        $payload = [];
        $payload['resource'] = ResourcesDirectory::NEW_REGISTRATION;
        $payload['agreement'] = $agreement;

        if ($email) {
            $payload['contact'] = ['mailto:'.$email];
        }

        return $this->requestResource('POST', ResourcesDirectory::NEW_REGISTRATION, $payload);
    }

    /**
     * {@inheritdoc}
     */
    public function requestChallenge($domain)
    {
        Assert::string($domain, 'AcmeClient::requestChallenge $domain expected a string. Got: %s');

        $payload = [
            'resource'   => ResourcesDirectory::NEW_AUTHORIZATION,
            'identifier' => [
                'type'  => 'dns',
                'value' => $domain,
            ],
        ];

        $response = $this->requestResource('POST', ResourcesDirectory::NEW_AUTHORIZATION, $payload);

        if (!isset($response['challenges']) || !$response['challenges']) {
            throw new HttpChallengeNotSupportedException();
        }

        $base64encoder = $this->httpClient->getBase64Encoder();
        $keyParser = $this->httpClient->getKeyParser();
        $accountKeyPair = $this->httpClient->getAccountKeyPair();

        $parsedKey = $keyParser->parse($accountKeyPair->getPrivateKey());

        foreach ($response['challenges'] as $challenge) {
            if ('http-01' === $challenge['type']) {
                $token = $challenge['token'];

                $header = [
                    // This order matters
                    'e'   => $base64encoder->encode($parsedKey->getDetail('e')),
                    'kty' => 'RSA',
                    'n'   => $base64encoder->encode($parsedKey->getDetail('n')),
                ];

                $payload = $token.'.'.$base64encoder->encode(hash('sha256', json_encode($header), true));
                $location = $this->httpClient->getLastLocation();

                return new Challenge($domain, $challenge['uri'], $token, $payload, $location);
            }
        }

        throw new HttpChallengeNotSupportedException();
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
     * Request a resource (URL is found using ACME server directory).
     *
     * @param string $method
     * @param string $resource
     * @param array  $payload
     * @param bool   $returnJson
     *
     * @throws AcmeCoreServerException When the ACME server returns an error HTTP status code.
     * @throws AcmeCoreClientException When an error occured during response parsing.
     *
     * @return array|string
     */
    protected function requestResource($method, $resource, array $payload, $returnJson = true)
    {
        if (!$this->directory) {
            $this->directory = new ResourcesDirectory(
                $this->httpClient->unsignedRequest('GET', $this->directoryUrl, null, true)
            );
        }

        return $this->httpClient->signedRequest(
            $method,
            $this->directory->getResourceUrl($resource),
            $payload,
            $returnJson
        );
    }
}
