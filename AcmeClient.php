<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core;

use AcmePhp\Core\Exception\AcmeCoreClientException;
use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Protocol\CertificateRequestFailedException;
use AcmePhp\Core\Exception\Protocol\CertificateRevocationException;
use AcmePhp\Core\Exception\Protocol\ChallengeFailedException;
use AcmePhp\Core\Exception\Protocol\ChallengeNotSupportedException;
use AcmePhp\Core\Exception\Protocol\ChallengeTimedOutException;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use AcmePhp\Core\Protocol\CertificateOrder;
use AcmePhp\Core\Protocol\ResourcesDirectory;
use AcmePhp\Core\Protocol\RevocationReason;
use AcmePhp\Ssl\Certificate;
use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\CertificateResponse;
use AcmePhp\Ssl\Signer\CertificateRequestSigner;
use Webmozart\Assert\Assert;

/**
 * ACME protocol client implementation.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeClient implements AcmeClientV2Interface
{
    /**
     * @var SecureHttpClient
     */
    private $uninitializedHttpClient;

    /**
     * @var SecureHttpClient
     */
    private $initializedHttpClient;

    /**
     * @var CertificateRequestSigner
     */
    private $csrSigner;

    /**
     * @var string
     */
    private $directoryUrl;

    /**
     * @var ResourcesDirectory
     */
    private $directory;

    /**
     * @var string
     */
    private $account;

    /**
     * @param string $directoryUrl
     */
    public function __construct(SecureHttpClient $httpClient, $directoryUrl, CertificateRequestSigner $csrSigner = null)
    {
        $this->uninitializedHttpClient = $httpClient;
        $this->directoryUrl = $directoryUrl;
        $this->csrSigner = $csrSigner ?: new CertificateRequestSigner();
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpClient()
    {
        if (!$this->initializedHttpClient) {
            $this->initializedHttpClient = $this->uninitializedHttpClient;

            $this->initializedHttpClient->setNonceEndpoint($this->getResourceUrl(ResourcesDirectory::NEW_NONCE));
        }

        return $this->initializedHttpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function registerAccount($agreement = null, $email = null)
    {
        Assert::nullOrString($agreement, 'registerAccount::$agreement expected a string or null. Got: %s');
        Assert::nullOrString($email, 'registerAccount::$email expected a string or null. Got: %s');

        $payload = [
            'termsOfServiceAgreed' => true,
            'contact' => [],
        ];

        if (\is_string($email)) {
            $payload['contact'][] = 'mailto:'.$email;
        }

        $this->requestResource('POST', ResourcesDirectory::NEW_ACCOUNT, $payload);
        $account = $this->getResourceAccount();
        $client = $this->getHttpClient();

        return $client->request('POST', $account, $client->signKidPayload($account, $account, null));
    }

    /**
     * {@inheritdoc}
     */
    public function requestAuthorization($domain)
    {
        $order = $this->requestOrder([$domain]);

        try {
            return $order->getAuthorizationChallenges($domain);
        } catch (AcmeCoreClientException $e) {
            throw new ChallengeNotSupportedException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requestOrder(array $domains)
    {
        Assert::allStringNotEmpty($domains, 'requestOrder::$domains expected a list of strings. Got: %s');

        $payload = [
            'identifiers' => array_map(
                function ($domain) {
                    return [
                        'type' => 'dns',
                        'value' => $domain,
                    ];
                },
                array_values($domains)
            ),
        ];

        $client = $this->getHttpClient();
        $resourceUrl = $this->getResourceUrl(ResourcesDirectory::NEW_ORDER);
        $response = $client->request('POST', $resourceUrl, $client->signKidPayload($resourceUrl, $this->getResourceAccount(), $payload));
        if (!isset($response['authorizations']) || !$response['authorizations']) {
            throw new ChallengeNotSupportedException();
        }

        $orderEndpoint = $client->getLastLocation();
        foreach ($response['authorizations'] as $authorizationEndpoint) {
            $authorizationsResponse = $client->request('POST', $authorizationEndpoint, $client->signKidPayload($authorizationEndpoint, $this->getResourceAccount(), null));
            $domain = (empty($authorizationsResponse['wildcard']) ? '' : '*.').$authorizationsResponse['identifier']['value'];
            foreach ($authorizationsResponse['challenges'] as $challenge) {
                $authorizationsChallenges[$domain][] = $this->createAuthorizationChallenge($authorizationsResponse['identifier']['value'], $challenge);
            }
        }

        return new CertificateOrder($authorizationsChallenges, $orderEndpoint);
    }

    /**
     * {@inheritdoc}
     */
    public function reloadAuthorization(AuthorizationChallenge $challenge)
    {
        $client = $this->getHttpClient();
        $challengeUrl = $challenge->getUrl();
        $response = (array) $client->request('POST', $challengeUrl, $client->signKidPayload($challengeUrl, $this->getResourceAccount(), null));

        return $this->createAuthorizationChallenge($challenge->getDomain(), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function challengeAuthorization(AuthorizationChallenge $challenge, $timeout = 180)
    {
        Assert::integer($timeout, 'challengeAuthorization::$timeout expected an integer. Got: %s');

        $endTime = time() + $timeout;
        $client = $this->getHttpClient();
        $challengeUrl = $challenge->getUrl();
        $response = (array) $client->request('POST', $challengeUrl, $client->signKidPayload($challengeUrl, $this->getResourceAccount(), null));
        if ('pending' === $response['status']) {
            $response = (array) $client->request('POST', $challengeUrl, $client->signKidPayload($challengeUrl, $this->getResourceAccount(), []));
        }

        // Waiting loop
        while (time() <= $endTime && (!isset($response['status']) || 'pending' === $response['status'])) {
            sleep(1);
            $response = (array) $client->request('POST', $challengeUrl, $client->signKidPayload($challengeUrl, $this->getResourceAccount(), null));
        }

        if (isset($response['status']) && 'pending' === $response['status']) {
            throw new ChallengeTimedOutException($response);
        }
        if (!isset($response['status']) || 'valid' !== $response['status']) {
            throw new ChallengeFailedException($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function requestCertificate($domain, CertificateRequest $csr, $timeout = 180)
    {
        Assert::stringNotEmpty($domain, 'requestCertificate::$domain expected a non-empty string. Got: %s');
        Assert::integer($timeout, 'requestCertificate::$timeout expected an integer. Got: %s');

        $order = $this->requestOrder(array_unique(array_merge([$domain], $csr->getDistinguishedName()->getSubjectAlternativeNames())));

        return $this->finalizeOrder($order, $csr, $timeout);
    }

    public function requestAlternateCertificate($domain, CertificateRequest $csr, $timeout = 180)
    {
        Assert::stringNotEmpty($domain, 'requestCertificate::$domain expected a non-empty string. Got: %s');
        Assert::integer($timeout, 'requestCertificate::$timeout expected an integer. Got: %s');

        $order = $this->requestOrder(array_unique(array_merge([$domain], $csr->getDistinguishedName()->getSubjectAlternativeNames())));

        /** @var \AcmePhp\Core\Protocol\CertificateOrder $order */
        $order = $order->returnAlternateCertificateIfAvailable();

        return $this->finalizeOrder($order, $csr, $timeout, true);
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeOrder(CertificateOrder $order, CertificateRequest $csr, $timeout = 180)
    {
        Assert::integer($timeout, 'finalizeOrder::$timeout expected an integer. Got: %s');

        $endTime = time() + $timeout;
        $client = $this->getHttpClient();
        $orderEndpoint = $order->getOrderEndpoint();
        $response = $client->request('POST', $orderEndpoint, $client->signKidPayload($orderEndpoint, $this->getResourceAccount(), null));
        if (\in_array($response['status'], ['pending', 'ready'])) {
            $humanText = ['-----BEGIN CERTIFICATE REQUEST-----', '-----END CERTIFICATE REQUEST-----'];

            $csrContent = $this->csrSigner->signCertificateRequest($csr);
            $csrContent = trim(str_replace($humanText, '', $csrContent));
            $csrContent = trim($client->getBase64Encoder()->encode(base64_decode($csrContent)));

            $response = $client->request('POST', $response['finalize'], $client->signKidPayload($response['finalize'], $this->getResourceAccount(), ['csr' => $csrContent]));
        }

        // Waiting loop
        while (time() <= $endTime && (!isset($response['status']) || \in_array($response['status'], ['pending', 'processing', 'ready']))) {
            sleep(1);
            $response = $client->request('POST', $orderEndpoint, $client->signKidPayload($orderEndpoint, $this->getResourceAccount(), null));
        }

        if ('valid' !== $response['status']) {
            throw new CertificateRequestFailedException('The order has not been validated');
        }

        if ($order->shouldReturnAlternateCertificateIfAvailable()) {
          $response_headers = $this->executeAcmeCertificateRequest($response['certificate'], false, true);

          // Check to see if response headers include link to alternate certificate download
          if (isset($response_headers['Link'][1])) {

            $matches = [];
            preg_match('/<(http.*acme\/cert\/.*\/\d)>;rel="alternate"/', $response_headers['Link'][1], $matches);

            // If response headers include valid alternate certificate link, return that certificate instead
            if (isset($matches[1])) {
              return $this->returnCertificateResponse($csr, $this->executeAcmeCertificateRequest($matches[1], true, false));
            }
          }
        }

        return $this->returnCertificateResponse($csr, $this->executeAcmeCertificateRequest($response['certificate'], true, false));

    }

    /**
     * {@inheritdoc}
     */
    public function revokeCertificate(Certificate $certificate, RevocationReason $revocationReason = null)
    {
        if (!$endpoint = $this->getResourceUrl(ResourcesDirectory::REVOKE_CERT)) {
            throw new CertificateRevocationException('This ACME server does not support certificate revocation.');
        }

        if (null === $revocationReason) {
            $revocationReason = RevocationReason::createDefaultReason();
        }

        openssl_x509_export(openssl_x509_read($certificate->getPEM()), $formattedPem);

        $formattedPem = str_ireplace('-----BEGIN CERTIFICATE-----', '', $formattedPem);
        $formattedPem = str_ireplace('-----END CERTIFICATE-----', '', $formattedPem);
        $client = $this->getHttpClient();
        $formattedPem = $client->getBase64Encoder()->encode(base64_decode(trim($formattedPem)));

        try {
            $client->request(
                'POST',
                $endpoint,
                $client->signKidPayload($endpoint, $this->getResourceAccount(), ['certificate' => $formattedPem, 'reason' => $revocationReason->getReasonType()]),
                false
            );
        } catch (AcmeCoreServerException $e) {
            throw new CertificateRevocationException($e->getMessage(), $e);
        } catch (AcmeCoreClientException $e) {
            throw new CertificateRevocationException($e->getMessage(), $e);
        }
    }

    /**
     * Find a resource URL.
     *
     * @param string $resource
     *
     * @return string
     */
    public function getResourceUrl($resource)
    {
        if (!$this->directory) {
            $this->directory = new ResourcesDirectory(
                $this->getHttpClient()->request('GET', $this->directoryUrl)
            );
        }

        return $this->directory->getResourceUrl($resource);
    }

    /**
     * Request a resource (URL is found using ACME server directory).
     *
     * @param string $method
     * @param string $resource
     * @param bool   $returnJson
     *
     * @throws AcmeCoreServerException when the ACME server returns an error HTTP status code
     * @throws AcmeCoreClientException when an error occured during response parsing
     *
     * @return array|string
     */
    protected function requestResource($method, $resource, array $payload, $returnJson = true)
    {
        $client = $this->getHttpClient();
        $endpoint = $this->getResourceUrl($resource);

        return $client->request(
            $method,
            $endpoint,
            $client->signJwkPayload($endpoint, $payload),
            $returnJson
        );
    }

    /**
     * Retrieve the resource account.
     *
     * @return string
     */
    private function getResourceAccount()
    {
        if (!$this->account) {
            $payload = [
                'onlyReturnExisting' => true,
            ];

            $this->requestResource('POST', ResourcesDirectory::NEW_ACCOUNT, $payload);
            $this->account = $this->getHttpClient()->getLastLocation();
        }

        return $this->account;
    }

    private function createAuthorizationChallenge($domain, array $response)
    {
        $base64encoder = $this->getHttpClient()->getBase64Encoder();

        return new AuthorizationChallenge(
            $domain,
            $response['status'],
            $response['type'],
            $response['url'],
            $response['token'],
            $response['token'].'.'.$base64encoder->encode($this->getHttpClient()->getJWKThumbprint())
        );
    }

    private function executeAcmeCertificateRequest(string $cert_url, bool $return_cert_as_string, bool $return_response_headers)
    {

      $client = $this->getHttpClient();

      return $client->request(
        'POST',
        $cert_url,
        $client->signKidPayload($cert_url, $this->getResourceAccount(), null),
        !$return_cert_as_string,
        $return_response_headers
      );

    }

    private function returnCertificateResponse(CertificateRequest $csr, string $certificate)
    {

      $certificatesChain = null;

      foreach (array_reverse(explode("\n\n", $certificate)) as $pem) {
          $certificatesChain = new Certificate($pem, $certificatesChain);
      }

      return new CertificateResponse($csr, $certificatesChain);

    }
}
