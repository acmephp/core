<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Http;

use AcmePhp\Core\Exception\AcmeCoreServerException;
use AcmePhp\Core\Exception\Server\AcmeCoreServerMalformedResponseException;
use AcmePhp\Ssl\KeyPair;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle HTTP client wrapper to send requests signed with the account KeyPair.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class SecureHttpClient
{
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var KeyPair
     */
    private $accountKeyPair;

    /**
     * @var ResponseInterface
     */
    private $lastResponse;

    /**
     * @param KeyPair $accountKeyPair
     * @param ClientInterface|null $httpClient
     */
    public function __construct(KeyPair $accountKeyPair, ClientInterface $httpClient = null)
    {
        $this->accountKeyPair = $accountKeyPair;
        $this->httpClient = $httpClient ?: new Client();
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol.
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $payload
     * @param bool   $returnJson
     *
     * @throws BadResponseException When the ACME server returns an error HTTP status code.
     * @throws AcmeCoreServerMalformedResponseException When the ACME server does not return valid JSON and $returnJson is true.
     *
     * @return array|string Array if the data is valid JSON, string otherwise.
     */
    public function signedRequest($method, $endpoint, array $payload, $returnJson = true)
    {
        $privateKey = $this->accountKeyPair->getPrivateKey()->getResource();
        $details = openssl_pkey_get_details($privateKey);

        $header = [
            'alg' => 'RS256',
            'jwk' => [
                'kty' => 'RSA',
                'n'   => Base64SafeEncoder::encode($details['rsa']['n']),
                'e'   => Base64SafeEncoder::encode($details['rsa']['e']),
            ],
        ];

        $protected = $header;
        $protected['nonce'] = $this->lastResponse->getHeaderLine('Replay-Nonce');

        $payload = Base64SafeEncoder::encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $protected = Base64SafeEncoder::encode(json_encode($protected));

        openssl_sign($protected.'.'.$payload, $signature, $privateKey, 'SHA256');
        $signature = Base64SafeEncoder::encode($signature);

        $payload = [
            'header'    => $header,
            'protected' => $protected,
            'payload'   => $payload,
            'signature' => $signature,
        ];

        return $this->unsignedRequest($method, $endpoint, $payload, $returnJson);
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array  $data
     * @param bool   $returnJson
     *
     * @throws BadResponseException When the ACME server returns an error HTTP status code.
     * @throws AcmeCoreServerMalformedResponseException When the ACME server does not return valid JSON and $returnJson is true.
     *
     * @return ResponseInterface
     */
    public function unsignedRequest($method, $endpoint, array $data = null, $returnJson = true)
    {
        $request = new Request($method, $endpoint);
        $request = $request->withHeader('Accept', 'application/json');

        if ('POST' === $method && is_array($data)) {
            $request = $request->withHeader('Content-Type', 'application/json');
            $request = $request->withBody(\GuzzleHttp\Psr7\stream_for(json_encode($data)));
        }

        try {
            $this->lastResponse = $this->httpClient->send($request);
        } catch (RequestException $exception) {
            if ($exception->getResponse() instanceof ResponseInterface) {
                $this->lastResponse = $exception->getResponse();
            }

            throw $exception;
        }

        $body = \GuzzleHttp\Psr7\copy_to_string($this->lastResponse->getBody());
        $data = @json_decode($body, true);

        if (! $data) {
            if ($returnJson) {
                throw new AcmeCoreServerMalformedResponseException($request);
            }

            return $body;
        }

        return $data;
    }

    /**
     * @return int
     */
    public function getLastCode()
    {
        return $this->lastResponse->getStatusCode();
    }

    /**
     * @return string
     */
    public function getLastLocation()
    {
        return $this->lastResponse->getHeaderLine('Location');
    }

    /**
     * @return string[]
     */
    public function getLastLinks()
    {
        return $this->lastResponse->getHeader('Link');
    }
}
