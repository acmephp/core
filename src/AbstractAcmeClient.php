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

use AcmePhp\Core\Exception\AccountKeyPairMissingException;
use AcmePhp\Core\Protocol\Challenge;
use AcmePhp\Core\Protocol\Exception\AcmeCertificateRequestFailedException;
use AcmePhp\Core\Protocol\Exception\AcmeCertificateRequestTimedOutException;
use AcmePhp\Core\Protocol\Exception\AcmeChallengeFailedException;
use AcmePhp\Core\Protocol\Exception\AcmeChallengeNotSupportedException;
use AcmePhp\Core\Protocol\Exception\AcmeChallengeTimedOutException;
use AcmePhp\Core\Protocol\SecureHttpClient;
use AcmePhp\Core\Ssl\Certificate;
use AcmePhp\Core\Ssl\CSR;
use AcmePhp\Core\Ssl\Exception\GeneratingCsrFailedException;
use AcmePhp\Core\Ssl\Exception\LoadingSslKeyFailedException;
use AcmePhp\Core\Ssl\KeyPair;
use AcmePhp\Core\Util\Base64UrlSafeEncoder;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Webmozart\Assert\Assert;

/**
 * Abstract basis for ACME protocol clients.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractAcmeClient implements AcmeClientInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KeyPair
     */
    private $accountKeyPair;

    /**
     * @var SecureHttpClient
     */
    private $httpClient;

    /**
     * Return the Certificate Authority API base URL.
     *
     * @return string
     */
    abstract protected function getCABaseUrl();

    /**
     * Return the Certificate Authority license document URL.
     *
     * @return string
     */
    abstract protected function getCALicense();

    /**
     * Create the client.
     *
     * @param KeyPair              $accountKeyPair The account KeyPair to use for dialog with the Certificate Authority.
     * @param LoggerInterface|null $logger
     */
    public function __construct(KeyPair $accountKeyPair = null, LoggerInterface $logger = null)
    {
        if ($accountKeyPair) {
            $this->useAccountKeyPair($accountKeyPair);
        }

        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function useAccountKeyPair(KeyPair $keyPair)
    {
        $this->accountKeyPair = $keyPair;
        $this->httpClient = new SecureHttpClient($this->getCABaseUrl(), $this->accountKeyPair);
    }

    /**
     * {@inheritdoc}
     */
    public function registerAccount($email = null)
    {
        if (!$this->accountKeyPair) {
            throw new AccountKeyPairMissingException();
        }

        Assert::nullOrString($email, 'registerAccount::$email expected a string or null. Got: %s');

        return $this->doRegisterAccount($email);
    }

    /**
     * {@inheritdoc}
     */
    public function requestChallenge($domain)
    {
        if (!$this->accountKeyPair) {
            throw new AccountKeyPairMissingException();
        }

        Assert::stringNotEmpty($domain, 'requestChallenge::$domain expected a non-empty string. Got: %s');

        return $this->doRequestChallenge($domain);
    }

    /**
     * {@inheritdoc}
     */
    public function checkChallenge(Challenge $challenge, $timeout = 180)
    {
        if (!$this->accountKeyPair) {
            throw new AccountKeyPairMissingException();
        }

        Assert::integer($timeout, 'checkChallenge::$timeout expected an integer. Got: %s');

        $this->doCheckChallenge($challenge, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function requestCertificate($domain, KeyPair $domainKeyPair, CSR $csr, $timeout = 180)
    {
        if (!$this->accountKeyPair) {
            throw new AccountKeyPairMissingException();
        }

        Assert::stringNotEmpty($domain, 'requestCertificate::$domain expected a non-empty string. Got: %s');
        Assert::integer($timeout, 'requestCertificate::$timeout expected an integer. Got: %s');

        return $this->doRequestCertificate($domain, $domainKeyPair, $csr, $timeout);
    }

    /**
     * @see requestAccount()
     */
    protected function doRegisterAccount($email)
    {
        $payload = [];
        $payload['resource'] = 'new-reg';
        $payload['agreement'] = $this->getCALicense();

        if ($email) {
            $payload['contact'] = ['mailto:'.$email];
        }

        $this->log(LogLevel::DEBUG, 'Registering account ...', [
            'server'  => $this->getCABaseUrl(),
            'payload' => $payload,
        ]);

        $response = $this->httpClient->request('POST', '/acme/new-reg', $payload);

        $this->log(LogLevel::INFO, 'Account registered', [
            'server'  => $this->getCABaseUrl(),
            'payload' => json_encode($payload),
        ]);

        return $response;
    }

    /**
     * @see requestChallenge()
     */
    protected function doRequestChallenge($domain)
    {
        $privateAccountKey = $this->accountKeyPair->getPrivateKey();
        $accountKeyDetails = openssl_pkey_get_details($privateAccountKey);

        $this->log(LogLevel::DEBUG, 'Requesting challenge for domain {domain} ...', [
            'domain' => $domain,
        ]);

        $response = $this->httpClient->request('POST', '/acme/new-authz', [
            'resource'   => 'new-authz',
            'identifier' => [
                'type'  => 'dns',
                'value' => $domain,
            ],
        ]);

        if (!isset($response['challenges']) || 0 === count($response['challenges'])) {
            throw new AcmeChallengeNotSupportedException();
        }

        foreach ($response['challenges'] as $challenge) {
            if ('http-01' === $challenge['type']) {
                $token = $challenge['token'];

                $header = [
                    // This order matters
                    'e'   => Base64UrlSafeEncoder::encode($accountKeyDetails['rsa']['e']),
                    'kty' => 'RSA',
                    'n'   => Base64UrlSafeEncoder::encode($accountKeyDetails['rsa']['n']),
                ];

                $payload = $token.'.'.Base64UrlSafeEncoder::encode(hash('sha256', json_encode($header), true));
                $location = $this->httpClient->getLastLocation();

                $this->log(LogLevel::INFO, 'Challenge data found for domain {domain}', [
                    'domain'   => $domain,
                    'token'    => $token,
                    'payload'  => $payload,
                    'location' => $location,
                ]);

                return new Challenge($domain, $challenge['uri'], $token, $payload, $location);
            }
        }

        throw new AcmeChallengeNotSupportedException();
    }

    /**
     * @see checkChallenge()
     */
    protected function doCheckChallenge(Challenge $challenge, $timeout)
    {
        $payload = [
            'resource'         => 'challenge',
            'type'             => 'http-01',
            'keyAuthorization' => $challenge->getPayload(),
            'token'            => $challenge->getToken(),
        ];

        $this->log(LogLevel::DEBUG, 'Asking server to check challenge on {url} ...', [
            'url' => sprintf(
                'http://%s/.well-known/acme-challenge/%s',
                $challenge->getDomain(),
                $challenge->getToken()
            ),
            'payload' => $payload,
        ]);

        $response = $this->httpClient->request('POST', $challenge->getUrl(), $payload);

        if (empty($response['status']) || 'invalid' === $response['status']) {
            throw new AcmeChallengeFailedException($response);
        }

        // Waiting loop
        $waitingTime = 0;

        while ($waitingTime < $timeout) {
            $response = $this->httpClient->request('GET', $challenge->getLocation(), []);

            if (empty($response['status']) || 'invalid' === $response['status']) {
                throw new AcmeChallengeFailedException($response);
            }

            if ('pending' !== $response['status']) {
                break;
            }

            $waitingTime++;
            sleep(1);
        }

        if ('pending' === $response['status']) {
            throw new AcmeChallengeTimedOutException($response);
        }

        $this->log(LogLevel::INFO, 'Challenge check succeded', [
            'url' => sprintf(
                'http://%s/.well-known/acme-challenge/%s',
                $challenge->getDomain(),
                $challenge->getToken()
            ),
            'payload' => $payload,
        ]);
    }

    /**
     * @see requestCertificate()
     */
    protected function doRequestCertificate($domain, KeyPair $domainKeyPair, CSR $csr, $timeout)
    {
        // CSR
        $csrData = $csr->toArray();
        $csrData['commonName'] = $domain;

        $this->log(LogLevel::DEBUG, 'Generating Certificate Signing Request ...', [
            'csrData' => $csrData,
        ]);
        
        $privateKey = $domainKeyPair->getPrivateKey();
        $csr = openssl_csr_new(
            $csrData,
            $privateKey,
            ['digest_alg' => 'sha256']
        );

        if (!$csr) {
            throw new GeneratingCsrFailedException(sprintf(
                'OpenSSL CSR generation failed with error: %s',
                openssl_error_string()
            ));
        }

        openssl_csr_export($csr, $csr);

        $humanText = ['-----BEGIN CERTIFICATE REQUEST-----', '-----END CERTIFICATE REQUEST-----'];
        $csrContent = trim(str_replace($humanText, '', $csr));
        $csrContent = trim(Base64UrlSafeEncoder::encode(base64_decode($csrContent)));

        $this->log(LogLevel::INFO, 'CSR generated successfully', [
            'csrData' => $csrData,
            'csr'     => $csr,
        ]);

        // Certificate
        $payload = [
            'resource' => 'new-cert',
            'csr'      => $csrContent,
        ];

        $this->log(LogLevel::DEBUG, 'Requesting a certificate for domain {domain} ...', [
            'domain'  => $domain,
            'payload' => $payload,
        ]);

        $response = $this->httpClient->request('POST', '/acme/new-cert', $payload);
        $location = $this->httpClient->getLastLocation();

        // Waiting loop
        $waitingTime = 0;

        while ($waitingTime < $timeout) {
            $response = $this->httpClient->unsignedRequest('GET', $location);

            if (200 === $this->httpClient->getLastCode()) {
                break;
            }

            if (202 !== $this->httpClient->getLastCode()) {
                throw new AcmeCertificateRequestFailedException($response);
            }

            $waitingTime++;
            sleep(1);
        }

        if (202 === $this->httpClient->getLastCode()) {
            throw new AcmeCertificateRequestTimedOutException($response);
        }

        $body = \GuzzleHttp\Psr7\copy_to_string($response->getBody());

        $this->log(LogLevel::INFO, 'Certificate request succeeded, parsing it ...', [
            'domain'   => $domain,
            'payload'  => $payload,
            'response' => $body,
        ]);

        $pem = chunk_split(base64_encode($body), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";

        $this->log(LogLevel::INFO, 'Certificate parsed successfully', [
            'domain'  => $domain,
            'payload' => $payload,
            'pem'     => $pem,
        ]);

        return new Certificate($domain, $domainKeyPair, $pem);
    }

    /**
     * Log a message into the logger if there is one.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    protected function log($level, $message, array $context = [])
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
