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
use AcmePhp\Core\Protocol\Exception\AcmeChallengeNotSupportedException;
use AcmePhp\Core\Protocol\SecureHttpClient;
use AcmePhp\Core\Ssl\Certificate;
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
     *
     * @throws LoadingSslKeyFailedException If the provided account keys can not be loaded by OpenSSL.
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

        return $this->doCheckChallenge($challenge, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function requestCertificate($domain, KeyPair $domainKeyPair, $timeout = 180)
    {
        if (!$this->accountKeyPair) {
            throw new AccountKeyPairMissingException();
        }

        Assert::stringNotEmpty($domain, 'requestCertificate::$domain expected a non-empty string. Got: %s');
        Assert::integer($timeout, 'requestCertificate::$timeout expected an integer. Got: %s');

        return $this->doRequestCertificate($domain, $domainKeyPair, $timeout);
    }

    /**
     * @param string|null $email An optionnal e-mail to associate with the
     *                           account.
     *
     * @return array The Certificate Authority response decoded from JSON into
     *               an array.
     */
    protected function doRegisterAccount($email)
    {
        $payload = [];
        $payload['resource'] = 'new-reg';
        $payload['agreement'] = $this->getCALicense();

        if ($email) {
            $payload['contact'] = ['mailto:'.$email];
        }

        $this->log(LogLevel::DEBUG, sprintf('Registering account with payload %s ...', json_encode($payload)));

        $response = $this->httpClient->request('POST', '/acme/new-reg', $payload);

        $this->log(LogLevel::DEBUG, 'Account registered');

        return $response;
    }

    /**
     * @param string $domain The domain to challenge.
     *
     * @return Challenge The data returned by the Certificate Authority.
     */
    protected function doRequestChallenge($domain)
    {
        $privateAccountKey = $this->accountKeyPair->getPrivateKey();
        $accountKeyDetails = openssl_pkey_get_details($privateAccountKey);

        $this->log(LogLevel::DEBUG, sprintf('Requesting challenge for domain %s ...', $domain));

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

                $this->log(LogLevel::DEBUG, sprintf('Challenge data successfully found: %s', $token));

                $header = [
                    // This order matters
                    'e'   => Base64UrlSafeEncoder::encode($accountKeyDetails['rsa']['e']),
                    'kty' => 'RSA',
                    'n'   => Base64UrlSafeEncoder::encode($accountKeyDetails['rsa']['n']),
                ];

                $payload = $token.'.'.Base64UrlSafeEncoder::encode(hash('sha256', json_encode($header), true));
                $location = $this->httpClient->getLastLocation();

                return new Challenge($domain, $challenge['uri'], $token, $payload, $location);
            }
        }

        throw new AcmeChallengeNotSupportedException();
    }

    /**
     * @param Challenge $challenge The challenge data to check.
     * @param int       $timeout   The timeout period.
     *
     * @return bool Whether the challenge was successfully checked or not.
     */
    protected function doCheckChallenge($challenge, $timeout)
    {
        $this->log(LogLevel::DEBUG, sprintf(
            'Asking server to challenge http://%s/.well-known/acme-challenge/%s ...',
            $challenge->getDomain(),
            $challenge->getToken()
        ));

        $payload = [
            'resource'         => 'challenge',
            'type'             => 'http-01',
            'keyAuthorization' => $challenge->getPayload(),
            'token'            => $challenge->getToken(),
        ];

        $response = $this->httpClient->request('POST', $challenge->getUrl(), $payload);

        if (empty($response['status']) || 'invalid' === $response['status']) {
            $this->log(LogLevel::ERROR, sprintf('Check challenge failed (body: %s)', json_encode($response)));

            return false;
        }

        // Waiting loop
        $waitingTime = 0;

        while ($waitingTime < $timeout) {
            $response = $this->httpClient->request('GET', $challenge->getLocation(), []);

            if (empty($response['status']) || 'invalid' === $response['status']) {
                $this->log(LogLevel::ERROR, sprintf('Check challenge failed (body: %s)', json_encode($response)));

                return false;
            }

            if ('pending' !== $response['status']) {
                break;
            }

            $waitingTime++;
            sleep(1);
        }

        if ('pending' === $response['status']) {
            $this->log(LogLevel::ERROR, sprintf('Check challenge request timed out (body: %s)', json_encode($response)));

            return false;
        }

        $this->log(LogLevel::ERROR, sprintf('Check challenge request succeded (body: %s)', json_encode($response)));

        return true;
    }

    /**
     * @param string  $domain        The domain to request a certificate for.
     * @param KeyPair $domainKeyPair The domain SSL KeyPair to use (for renewal).
     * @param int     $timeout       The timeout period.
     *
     * @return Certificate The certificate data to save somewhere you want.
     */
    protected function doRequestCertificate($domain, KeyPair $domainKeyPair, $timeout)
    {
        // TODO
    }

    /**
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
