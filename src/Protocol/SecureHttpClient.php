<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Protocol;

use AcmePhp\Core\Protocol\Exception\AcmeHttpErrorException;
use AcmePhp\Core\Ssl\KeyPair;
use AcmePhp\Core\Util\Base64UrlSafeEncoder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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
     * @param string  $authority
     * @param KeyPair $accountKeyPair
     */
    public function __construct($authority, KeyPair $accountKeyPair)
    {
        $this->accountKeyPair = $accountKeyPair;
        $this->httpClient = new Client([
            'base_uri' => $authority,
        ]);
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol.
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $payload
     *
     * @return array|string Array if the data is valid JSON, string otherwise.
     */
    public function request($method, $endpoint, array $payload)
    {
        $privateKey = $this->accountKeyPair->getPrivateKey();
        $details = openssl_pkey_get_details($privateKey);

        $header = [
            'alg' => 'RS256',
            'jwk' => [
                'kty' => 'RSA',
                'n'   => Base64UrlSafeEncoder::encode($details['rsa']['n']),
                'e'   => Base64UrlSafeEncoder::encode($details['rsa']['e']),
            ],
        ];

        $protected = $header;
        $protected['nonce'] = $this->lastResponse->getHeaderLine('Replay-Nonce');

        $payload = Base64UrlSafeEncoder::encode(str_replace('\\/', '/', json_encode($payload)));
        $protected = Base64UrlSafeEncoder::encode(json_encode($protected));

        openssl_sign($protected.'.'.$payload, $signature, $privateKey, 'SHA256');
        $signature = Base64UrlSafeEncoder::encode($signature);

        $payload = [
            'header'    => $header,
            'protected' => $protected,
            'payload'   => $payload,
            'signature' => $signature,
        ];

        $this->unsignedRequest($method, $endpoint, $payload);

        $body = \GuzzleHttp\Psr7\copy_to_string($this->lastResponse->getBody());
        $data = @json_decode($body, true);

        if (!$data) {
            return $body;
        }

        return $data;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array  $data
     *
     * @throws AcmeHttpErrorException When the ACME server returns an error HTTP status code.
     *
     * @return ResponseInterface
     */
    public function unsignedRequest($method, $endpoint, array $data = null)
    {
        $request = new Request($method, $endpoint);
        $request = $request->withHeader('Accept', 'application/json');

        if ('POST' === $method && is_array($data)) {
            $request = $request->withHeader('Content-Type', 'application/json');
            $request = $request->withBody(\GuzzleHttp\Psr7\stream_for(json_encode($data)));
        }

        try {
            $this->lastResponse = $this->httpClient->send($request);
        } catch (ClientException $exception) {
            $this->lastResponse = $exception->getResponse();
            
            throw new AcmeHttpErrorException($request, $exception);
        } catch (\Exception $exception) {
            throw new AcmeHttpErrorException($request, $exception);
        }

        return $this->lastResponse;
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
    public function getLastLink()
    {
        return $this->lastResponse->getHeader('Link');
    }
}
